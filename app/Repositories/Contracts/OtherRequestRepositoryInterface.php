<?php

namespace App\Repositories\Contracts;

use App\Models\OtherRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OtherRequestRepositoryInterface
{
    public function forEmployee(int $orgId, int $employeeId, ?string $status): LengthAwarePaginator;

    public function pendingForApprover(int $orgId, int $approvingUserId, bool $isFinalApprover): LengthAwarePaginator;

    public function findOrFail(int $id): OtherRequest;

    public function create(array $data): OtherRequest;

    public function approve(OtherRequest $request, int $approverUserId): OtherRequest;

    public function reject(OtherRequest $request, int $rejectorUserId, string $reason): OtherRequest;
}
