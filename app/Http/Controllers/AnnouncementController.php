<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use App\Models\Department;
use App\Services\AnnouncementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function __construct(private readonly AnnouncementService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->can('announcements.view'), 403);

        $user = $request->user();
        $canCreate = $user->can('announcements.create');
        $announcements = $this->service->list((int) $user->org_id, $canCreate);

        $departments = $canCreate
            ? Department::query()->where('org_id', $user->org_id)->orderBy('name')->get(['id', 'name'])
            : collect();

        return Inertia::render('Announcements/Index', [
            'announcements' => $announcements->through(fn ($a) => $this->serialize($a)),
            'can_create' => $canCreate,
            'departments' => $departments->map(fn ($d) => ['id' => $d->id, 'name' => $d->name])->values(),
        ]);
    }

    public function show(int $announcement, Request $request): Response
    {
        abort_unless($request->user()->can('announcements.view'), 403);

        $item = $this->service->get($announcement);
        abort_unless($item->org_id === (int) $request->user()->org_id, 403);

        return Inertia::render('Announcements/Show', [
            'announcement' => $this->serialize($item),
            'can_edit' => $request->user()->can('announcements.create'),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if (! isset($data['published_at']) && $request->boolean('publish_now')) {
            $data['published_at'] = now()->toDateTimeString();
        }

        $announcement = $this->service->create(
            (int) $request->user()->org_id,
            $request->user(),
            $data,
        );

        return redirect()->route('announcements.show', $announcement->id)
            ->with('status', 'Announcement created.');
    }

    public function update(int $announcement, UpdateAnnouncementRequest $request): RedirectResponse
    {
        $item = $this->service->get($announcement);
        abort_unless($item->org_id === (int) $request->user()->org_id, 403);

        $this->service->update($announcement, $request->validated());

        return redirect()->route('announcements.show', $announcement)
            ->with('status', 'Announcement updated.');
    }

    public function publish(int $announcement, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('announcements.create'), 403);

        $item = $this->service->get($announcement);
        abort_unless($item->org_id === (int) $request->user()->org_id, 403);

        $this->service->publish($announcement);

        return redirect()->route('announcements.show', $announcement)
            ->with('status', 'Announcement published.');
    }

    public function destroy(int $announcement, Request $request): RedirectResponse
    {
        abort_unless($request->user()->can('announcements.create'), 403);

        $item = $this->service->get($announcement);
        abort_unless($item->org_id === (int) $request->user()->org_id, 403);

        $this->service->delete($announcement);

        return redirect()->route('announcements.index')
            ->with('status', 'Announcement deleted.');
    }

    /** @return array<string, mixed> */
    private function serialize($a): array
    {
        return [
            'id' => $a->id,
            'title' => $a->title,
            'body' => $a->body,
            'target_type' => $a->target_type,
            'target_id' => $a->target_id,
            'pinned' => $a->pinned,
            'published_at' => $a->published_at?->toIso8601String(),
            'expires_at' => $a->expires_at?->toIso8601String(),
            'author' => $a->author ? ['id' => $a->author->id, 'name' => $a->author->name] : null,
            'department' => $a->department ? ['id' => $a->department->id, 'name' => $a->department->name] : null,
            'created_at' => $a->created_at?->toIso8601String(),
        ];
    }
}
