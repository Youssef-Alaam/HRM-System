<?php

namespace App\Repositories\Contracts;

use App\Models\Position;
use Illuminate\Database\Eloquent\Collection;

interface PositionRepositoryInterface
{
    public function allForOrg(int $orgId): Collection;

    public function findOrFail(int $id): Position;

    public function create(array $data): Position;

    public function update(Position $position, array $data): Position;

    public function softDelete(Position $position): void;
}
