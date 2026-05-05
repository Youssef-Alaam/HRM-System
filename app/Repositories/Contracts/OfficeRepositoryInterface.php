<?php

namespace App\Repositories\Contracts;

use App\Models\Office;
use Illuminate\Database\Eloquent\Collection;

interface OfficeRepositoryInterface
{
    public function allForOrg(int $orgId): Collection;

    public function findOrFail(int $id): Office;

    public function create(array $data): Office;

    public function update(Office $office, array $data): Office;

    public function softDelete(Office $office): void;
}
