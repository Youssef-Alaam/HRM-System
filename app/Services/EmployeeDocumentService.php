<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Repositories\Contracts\EmployeeDocumentRepositoryInterface;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Layer-3 service for employee documents.
 *
 * Locked 2026-04-30 with Walid:
 * - HR-only writes (employees and managers can't upload).
 * - File limits enforced at the FormRequest layer (10 MB, PDF/JPEG/PNG/WebP).
 * - 30 documents per employee cap enforced here so the matrix view and
 *   uploads share the same gate.
 * - Replace flow soft-deletes the old (employee, type) row before
 *   creating the new one — the Auditable trait writes both events.
 *
 * File layout: storage/app/employee-documents/{employee_id}/{uuid}.{ext}
 */
class EmployeeDocumentService extends BaseService
{
    public const PER_EMPLOYEE_CAP = 30;

    public const STORAGE_DISK = 'local';

    public const STORAGE_DIR = 'employee-documents';

    public function __construct(
        private readonly EmployeeDocumentRepositoryInterface $repo,
    ) {}

    public function upload(
        Employee $employee,
        UploadedFile $file,
        ?int $documentTypeId,
        ?string $issuedDate,
        ?string $expiryDate,
        ?string $notes,
        int $uploadedByUserId,
    ): EmployeeDocument {
        return $this->transaction(function () use (
            $employee, $file, $documentTypeId, $issuedDate, $expiryDate, $notes, $uploadedByUserId,
        ) {
            // Replace flow: if the (employee, type) slot is already taken,
            // soft-delete the old row first. The unique audit chain shows
            // upload → soft-delete → upload across the type's history.
            if ($documentTypeId !== null) {
                $existing = $this->repo->findByEmployeeAndType($employee->id, $documentTypeId);
                if ($existing) {
                    $this->repo->softDelete($existing);
                }
            }

            // Cap check AFTER the replace — the slot we just freed counts
            // against the freed total, so the upcoming upload still fits.
            if ($this->repo->countActiveForEmployee($employee->id) >= self::PER_EMPLOYEE_CAP) {
                throw new DomainException(
                    'Maximum '.self::PER_EMPLOYEE_CAP.' documents per employee. Delete an existing document first.',
                );
            }

            $stored = $this->store($employee, $file);

            return $this->repo->create([
                'org_id' => $employee->org_id,
                'employee_id' => $employee->id,
                'document_type_id' => $documentTypeId,
                'file_path' => $stored['path'],
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'file_size_bytes' => (int) $file->getSize(),
                'issued_date' => $issuedDate,
                'expiry_date' => $expiryDate,
                'notes' => $notes,
                'uploaded_by_user_id' => $uploadedByUserId,
                'uploaded_at' => CarbonImmutable::now(),
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data  Validated metadata-only patch
     */
    public function updateMetadata(int $id, array $data): EmployeeDocument
    {
        return $this->transaction(function () use ($id, $data) {
            $document = $this->repo->findOrFail($id);

            return $this->repo->update($document, $data);
        });
    }

    public function softDelete(int $id): void
    {
        $this->transaction(function () use ($id) {
            $document = $this->repo->findOrFail($id);
            $this->repo->softDelete($document);
            // We deliberately leave the file on disk — soft-deleted rows
            // can be restored from the audit trail, and an HR mistake
            // shouldn't make documents unrecoverable. A nightly purge job
            // can sweep orphaned files later (Phase 2).
        });
    }

    /**
     * Storage helpers — kept inside the service so controllers don't
     * touch the filesystem directly. PER CLAUDE.md: services hold
     * business logic; here that includes file IO.
     *
     * @return array{path: string}
     */
    private function store(Employee $employee, UploadedFile $file): array
    {
        $ext = $file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.$ext;
        $relativeDir = self::STORAGE_DIR.'/'.$employee->id;
        $path = $file->storeAs($relativeDir, $filename, self::STORAGE_DISK);

        return ['path' => $path];
    }

    public static function diskPath(string $relative): string
    {
        return Storage::disk(self::STORAGE_DISK)->path($relative);
    }
}
