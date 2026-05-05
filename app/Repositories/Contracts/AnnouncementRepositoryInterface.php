<?php

namespace App\Repositories\Contracts;

use App\Models\Announcement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AnnouncementRepositoryInterface
{
    public function listForOrg(int $orgId, bool $draftsIncluded): LengthAwarePaginator;

    public function findOrFail(int $id): Announcement;

    public function create(array $data): Announcement;

    public function update(Announcement $announcement, array $data): Announcement;

    public function softDelete(Announcement $announcement): void;

    public function publish(Announcement $announcement): Announcement;
}
