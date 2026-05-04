<?php

namespace App\Repositories;

use App\Models\FaceEnrollment;
use App\Repositories\Contracts\FaceEnrollmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class FaceEnrollmentRepository extends BaseRepository implements FaceEnrollmentRepositoryInterface
{
    protected function model(): string
    {
        return FaceEnrollment::class;
    }

    public function find(int $id): ?FaceEnrollment
    {
        /** @var FaceEnrollment|null */
        return parent::find($id);
    }

    public function findOrFail(int $id): FaceEnrollment
    {
        /** @var FaceEnrollment */
        return parent::findOrFail($id);
    }

    /**
     * @return Collection<int, FaceEnrollment>
     */
    public function forEmployee(int $employeeId): Collection
    {
        /** @var Collection<int, FaceEnrollment> */
        return $this->query()
            ->with('enrolledBy:id,name')
            ->where('employee_id', $employeeId)
            ->orderByDesc('enrolled_at')
            ->get();
    }

    public function latestForEmployee(int $employeeId): ?FaceEnrollment
    {
        /** @var FaceEnrollment|null */
        return $this->query()
            ->where('employee_id', $employeeId)
            ->orderByDesc('enrolled_at')
            ->first();
    }

    public function create(array $data): FaceEnrollment
    {
        /** @var FaceEnrollment */
        return parent::create($data);
    }

    public function update(Model $enrollment, array $data): FaceEnrollment
    {
        /** @var FaceEnrollment */
        return parent::update($enrollment, $data);
    }
}
