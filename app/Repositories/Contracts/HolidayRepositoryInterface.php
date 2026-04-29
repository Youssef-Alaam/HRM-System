<?php

namespace App\Repositories\Contracts;

use App\Models\Holiday;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Parameter types use Eloquent\Model to remain compatible with BaseRepository
 * (PHP forbids covariant parameter narrowing). Return types are narrowed to
 * Holiday so callers get type-safe results.
 */
interface HolidayRepositoryInterface
{
    public function find(int $id): ?Holiday;

    public function findOrFail(int $id): Holiday;

    /**
     * @return Collection<int, Holiday>
     */
    public function all(): Collection;

    public function paginate(int $perPage = 25): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Holiday;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $holiday, array $data): Holiday;

    public function delete(Model $holiday): bool;

    public function restore(Model $holiday): bool;

    /**
     * @return Collection<int, Holiday>
     */
    public function inDateRange(string $start, string $end): Collection;

    public function findByDate(string $date): ?Holiday;
}
