<?php

namespace App\Repositories;

use App\Models\LeaveType;
use App\Repositories\Contracts\LeaveTypeRepositoryInterface;
use Illuminate\Support\Collection;

class LeaveTypeRepository extends BaseRepository implements LeaveTypeRepositoryInterface
{
    protected function model(): string
    {
        return LeaveType::class;
    }

    public function allActive(): Collection
    {
        return LeaveType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?LeaveType
    {
        return LeaveType::query()->find($id);
    }
}
