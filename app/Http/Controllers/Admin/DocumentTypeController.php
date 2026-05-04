<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDocumentTypeRequest;
use App\Http\Requests\UpdateDocumentTypeRequest;
use App\Models\DocumentType;
use App\Repositories\Contracts\DocumentTypeRepositoryInterface;
use App\Services\DocumentTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Settings → Document types (Admin only). Edits the canonical required
 * matrix per org. Permission middleware on the route group.
 */
class DocumentTypeController extends Controller
{
    public function __construct(
        private readonly DocumentTypeService $service,
        private readonly DocumentTypeRepositoryInterface $repo,
    ) {}

    public function index(Request $request)
    {
        $orgId = $request->user()->org_id;

        $types = DocumentType::query()
            ->where('org_id', $orgId)
            ->orderBy('order_index')
            ->orderBy('name')
            ->get();

        return Inertia::render('Settings/DocumentTypes', [
            'types' => $types->map(fn (DocumentType $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'name_ar' => $t->name_ar,
                'description' => $t->description,
                'applies_to' => $t->applies_to,
                'is_required' => (bool) $t->is_required,
                'default_expiry_months' => $t->default_expiry_months,
                'order_index' => $t->order_index,
                'is_active' => (bool) $t->is_active,
            ])->values()->all(),
        ]);
    }

    public function store(StoreDocumentTypeRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('admin.document-types.index')
            ->with('status', 'Document type added.');
    }

    public function update(int $type, UpdateDocumentTypeRequest $request): RedirectResponse
    {
        $this->service->update($type, $request->validated());

        return redirect()->route('admin.document-types.index')
            ->with('status', 'Document type updated.');
    }

    public function destroy(int $type, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('settings.document_types.manage'), 403);

        $this->service->disable($type);

        return redirect()->route('admin.document-types.index')
            ->with('status', 'Document type disabled.');
    }
}
