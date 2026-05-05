<?php

namespace App\Http\Controllers;

use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('audit.view'), 403);

        $orgId = (int) $request->user()->org_id;
        $filters = $request->only(['action', 'entity_type', 'user_id', 'from', 'to']);

        $entries = $this->service->paginate($orgId, $filters);

        return Inertia::render('AuditLog/Index', [
            'entries' => $entries->through(fn ($e) => [
                'id' => $e->id,
                'action' => $e->action,
                'entity_type' => class_basename((string) $e->entity_type),
                'entity_id' => $e->entity_id,
                'user' => $e->user ? ['name' => $e->user->name, 'email' => $e->user->email] : null,
                'ip_address' => $e->ip_address,
                'changes' => $e->changes,
                'created_at' => $e->created_at?->toIso8601String(),
            ]),
            'filters' => $filters,
            'actions' => $this->service->actionsForOrg($orgId),
        ]);
    }
}
