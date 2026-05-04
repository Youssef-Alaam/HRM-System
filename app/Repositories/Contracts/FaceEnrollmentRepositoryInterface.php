<?php

namespace App\Repositories\Contracts;

use App\Models\FaceEnrollment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface FaceEnrollmentRepositoryInterface
{
    public function find(int $id): ?FaceEnrollment;

    public function findOrFail(int $id): FaceEnrollment;

    /**
     * History rows for an employee, newest first.
     *
     * @return Collection<int, FaceEnrollment>
     */
    public function forEmployee(int $employeeId): Collection;

    public function latestForEmployee(int $employeeId): ?FaceEnrollment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FaceEnrollment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $enrollment, array $data): FaceEnrollment;
}
