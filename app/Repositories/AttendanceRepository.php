<?php

namespace App\Repositories;

use App\Models\AttendanceRecord;
use App\Repositories\Contracts\AttendanceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AttendanceRepository extends BaseRepository implements AttendanceRepositoryInterface
{
    protected function model(): string
    {
        return AttendanceRecord::class;
    }

    public function create(array $data): AttendanceRecord
    {
        /** @var AttendanceRecord */
        return AttendanceRecord::create($data);
    }

    public function findOrFail(int $id): AttendanceRecord
    {
        /** @var AttendanceRecord */
        return AttendanceRecord::query()
            ->with('employee:id,first_name,last_name', 'office:id,name', 'correctedBy:id,name')
            ->findOrFail($id);
    }

    public function update(Model $record, array $data): AttendanceRecord
    {
        /** @var AttendanceRecord $record */
        $record->update($data);

        return $record->refresh();
    }

    public function lastEventForEmployeeToday(int $orgId, int $employeeId, string $cairoDate): ?AttendanceRecord
    {
        return AttendanceRecord::query()
            ->where('org_id', $orgId)
            ->where('employee_id', $employeeId)
            ->whereDate('event_date', $cairoDate)
            ->orderByDesc('event_at')
            ->first();
    }

    public function todayForEmployee(int $orgId, int $employeeId, string $cairoDate): Collection
    {
        return AttendanceRecord::query()
            ->where('org_id', $orgId)
            ->where('employee_id', $employeeId)
            ->whereDate('event_date', $cairoDate)
            ->orderBy('event_at')
            ->get();
    }

    public function paginateForEmployee(int $orgId, int $employeeId): LengthAwarePaginator
    {
        return AttendanceRecord::query()
            ->where('org_id', $orgId)
            ->where('employee_id', $employeeId)
            ->with('office:id,name')
            ->orderByDesc('event_at')
            ->paginate(30)
            ->withQueryString();
    }

    public function paginateForOrg(int $orgId, array $filters): LengthAwarePaginator
    {
        return AttendanceRecord::query()
            ->where('org_id', $orgId)
            ->when($filters['employee_id'] ?? null, fn ($q, $id) => $q->where('employee_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->where('event_date', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->where('event_date', '<=', $d))
            ->when($filters['verdict'] ?? null, fn ($q, $v) => $q->where('verdict', $v))
            ->with('employee:id,first_name,last_name', 'office:id,name')
            ->orderByDesc('event_at')
            ->paginate(50)
            ->withQueryString();
    }

    public function selfiesDueForPurge(\DateTimeInterface $cutoff): Collection
    {
        return AttendanceRecord::withoutGlobalScopes()
            ->whereNotNull('selfie_path')
            ->whereNull('selfie_deleted_at')
            ->where('event_at', '<=', $cutoff)
            ->get();
    }
}
