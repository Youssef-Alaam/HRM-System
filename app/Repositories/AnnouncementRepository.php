<?php

namespace App\Repositories;

use App\Models\Announcement;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class AnnouncementRepository extends BaseRepository implements AnnouncementRepositoryInterface
{
    protected function model(): string
    {
        return Announcement::class;
    }

    public function listForOrg(int $orgId, bool $draftsIncluded): LengthAwarePaginator
    {
        $query = Announcement::query()
            ->where('org_id', $orgId)
            ->with('author:id,name', 'department:id,name')
            ->orderByDesc('pinned')
            ->orderByDesc('published_at');

        if (! $draftsIncluded) {
            $query->published();
        }

        return $query->paginate(20)->withQueryString();
    }

    public function findOrFail(int $id): Announcement
    {
        /** @var Announcement */
        return Announcement::query()->with('author:id,name', 'department:id,name')->findOrFail($id);
    }

    public function create(array $data): Announcement
    {
        /** @var Announcement */
        return Announcement::create($data);
    }

    public function update(Model $announcement, array $data): Announcement
    {
        /** @var Announcement $announcement */
        $announcement->update($data);

        return $announcement->refresh();
    }

    public function softDelete(Announcement $announcement): void
    {
        $announcement->delete();
    }

    public function publish(Announcement $announcement): Announcement
    {
        $announcement->update(['published_at' => now()]);

        return $announcement->refresh();
    }
}
