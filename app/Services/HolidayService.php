<?php

namespace App\Services;

use App\Models\Holiday;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class HolidayService extends BaseService
{
    public function __construct(
        private readonly HolidayRepositoryInterface $holidays,
    ) {}

    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return $this->holidays->paginate($perPage);
    }

    /**
     * @return Collection<int, Holiday>
     */
    public function listForYear(int $year): Collection
    {
        return $this->holidays->inDateRange("{$year}-01-01", "{$year}-12-31");
    }

    public function find(int $id): Holiday
    {
        return $this->holidays->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Holiday
    {
        return $this->transaction(fn () => $this->holidays->create($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): Holiday
    {
        return $this->transaction(function () use ($id, $data) {
            $holiday = $this->holidays->findOrFail($id);

            return $this->holidays->update($holiday, $data);
        });
    }

    public function delete(int $id): void
    {
        $this->transaction(function () use ($id) {
            $holiday = $this->holidays->findOrFail($id);
            $this->holidays->delete($holiday);
        });
    }
}
