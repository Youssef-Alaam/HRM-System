<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use App\Repositories\Contracts\AnnouncementRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AnnouncementService extends BaseService
{
    public function __construct(private readonly AnnouncementRepositoryInterface $repo) {}

    public function list(int $orgId, bool $canSeeDrafts): LengthAwarePaginator
    {
        return $this->repo->listForOrg($orgId, $canSeeDrafts);
    }

    public function get(int $id): Announcement
    {
        return $this->repo->findOrFail($id);
    }

    public function create(int $orgId, User $author, array $data): Announcement
    {
        return $this->transaction(function () use ($orgId, $author, $data) {
            return $this->repo->create(array_merge($data, [
                'org_id' => $orgId,
                'author_id' => $author->id,
            ]));
        });
    }

    public function update(int $id, array $data): Announcement
    {
        return $this->transaction(function () use ($id, $data) {
            $announcement = $this->repo->findOrFail($id);

            return $this->repo->update($announcement, $data);
        });
    }

    public function publish(int $id): Announcement
    {
        return $this->transaction(function () use ($id) {
            $announcement = $this->repo->findOrFail($id);

            return $this->repo->publish($announcement);
        });
    }

    public function delete(int $id): void
    {
        $this->transaction(function () use ($id) {
            $announcement = $this->repo->findOrFail($id);
            $this->repo->softDelete($announcement);
        });
    }
}
