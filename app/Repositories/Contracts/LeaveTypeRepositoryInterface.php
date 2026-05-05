<?php

namespace App\Repositories\Contracts;

use App\Models\LeaveType;
use Illuminate\Support\Collection;

interface LeaveTypeRepositoryInterface
{
    /** @return Collection<int, LeaveType> */
    public function allActive(): Collection;

    public function find(int $id): ?LeaveType;
}
