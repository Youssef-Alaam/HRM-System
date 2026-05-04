<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeDocumentRequest;
use App\Http\Requests\UpdateEmployeeDocumentRequest;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use App\Repositories\Contracts\EmployeeRepositoryInterface;
use App\Services\EmployeeDocumentService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Per-employee document upload / replace / delete / download.
 * Locked 2026-04-30 with Walid: HR + Admin only on every action.
 */
class EmployeeDocumentController extends Controller
{
    public function __construct(
        private readonly EmployeeDocumentService $service,
        private readonly EmployeeDocumentRepositoryInterface $repo,
        private readonly EmployeeRepositoryInterface $employees,
    ) {}

    public function store(int $employee, StoreEmployeeDocumentRequest $request): RedirectResponse
    {
        $employeeRow = $this->employees->findOrFail($employee);

        try {
            $this->service->upload(
                employee: $employeeRow,
                file: $request->file('file'),
                documentTypeId: $request->input('document_type_id') ? (int) $request->input('document_type_id') : null,
                issuedDate: $request->input('issued_date'),
                expiryDate: $request->input('expiry_date'),
                notes: $request->input('notes'),
                uploadedByUserId: (int) $request->user()->id,
            );
        } catch (DomainException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return back()->with('status', 'Document uploaded.');
    }

    public function update(int $employee, int $document, UpdateEmployeeDocumentRequest $request): RedirectResponse
    {
        // Sanity-check that the document belongs to this employee.
        $row = $this->repo->findOrFail($document);
        abort_unless($row->employee_id === $employee, 404);

        $this->service->updateMetadata($document, $request->validated());

        return back()->with('status', 'Document metadata updated.');
    }

    public function destroy(int $employee, int $document, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('documents.delete'), 403);

        $row = $this->repo->findOrFail($document);
        abort_unless($row->employee_id === $employee, 404);

        $this->service->softDelete($document);

        return back()->with('status', 'Document deleted.');
    }

    public function download(int $employee, int $document, Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('documents.view.any'), 403);

        $row = $this->repo->findOrFail($document);
        abort_unless($row->employee_id === $employee, 404);

        $disk = Storage::disk(EmployeeDocumentService::STORAGE_DISK);
        abort_unless($disk->exists($row->file_path), 404);

        return $disk->download($row->file_path, $row->original_filename, [
            'Content-Type' => $row->mime_type,
        ]);
    }

    /**
     * Per-employee documents list with the required-doc matrix overlay.
     * Used by the Documents tab on Employees/Show.
     *
     * @return array<string, mixed>
     */
    public static function buildMatrixView(Employee $employee, EmployeeDocumentRepositoryInterface $repo): array
    {
        $documents = $repo->forEmployee($employee->id);

        $byType = $documents->groupBy('document_type_id');

        return [
            'documents' => $documents->map(fn (EmployeeDocument $d) => [
                'id' => $d->id,
                'document_type_id' => $d->document_type_id,
                'document_type_name' => $d->documentType?->name,
                'original_filename' => $d->original_filename,
                'mime_type' => $d->mime_type,
                'file_size_bytes' => $d->file_size_bytes,
                'issued_date' => optional($d->issued_date)->toDateString(),
                'expiry_date' => optional($d->expiry_date)->toDateString(),
                'is_expired' => $d->isExpired(),
                'uploaded_at' => $d->uploaded_at?->toIso8601String(),
                'uploaded_by' => $d->uploader?->name,
                'notes' => $d->notes,
            ])->all(),
            'has_uploaded_for_type' => $byType
                ->mapWithKeys(fn ($rows, $typeId) => [(int) $typeId => true])
                ->all(),
        ];
    }
}
