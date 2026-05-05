<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOfficeRequest;
use App\Http\Requests\UpdateOfficeRequest;
use App\Services\OfficeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OfficeController extends Controller
{
    public function __construct(private readonly OfficeService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('org.offices.manage'), 403);

        $orgId = (int) $request->user()->org_id;

        return Inertia::render('Settings/Offices', [
            'offices' => $this->service->listForOrg($orgId)->map(fn ($o) => [
                'id' => $o->id,
                'name' => $o->name,
                'address' => $o->address,
                'latitude' => $o->latitude,
                'longitude' => $o->longitude,
                'allowed_check_in_radius_meters' => $o->allowed_check_in_radius_meters,
                'timezone' => $o->timezone,
                'is_active' => $o->is_active,
            ])->values(),
        ]);
    }

    public function store(StoreOfficeRequest $request): RedirectResponse
    {
        $this->service->create((int) $request->user()->org_id, $request->validated());

        return back()->with('status', 'Office created.');
    }

    public function update(int $office, UpdateOfficeRequest $request): RedirectResponse
    {
        $this->service->update($office, $request->validated());

        return back()->with('status', 'Office updated.');
    }

    public function destroy(int $office, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('org.offices.manage'), 403);

        $this->service->delete($office);

        return back()->with('status', 'Office deleted.');
    }
}
