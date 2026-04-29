<?php

namespace App\Repositories;

use App\Models\Holiday;
use App\Repositories\Contracts\HolidayRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class HolidayRepository extends BaseRepository implements HolidayRepositoryInterface
{
    protected function model(): string
    {
        return Holiday::class;
    }

    public function find(int $id): ?Holiday
    {
        /** @var Holiday|null */
        return parent::find($id);
    }

    public function findOrFail(int $id): Holiday
    {
        /** @var Holiday */
        return parent::findOrFail($id);
    }

    public function create(array $data): Holiday
    {
        /** @var Holiday */
        return parent::create($data);
    }

    public function update(Model $holiday, array $data): Holiday
    {
        /** @var Holiday */
        return parent::update($holiday, $data);
    }

    /**
     * @return Collection<int, Holiday>
     */
    public function inDateRange(string $start, string $end): Collection
    {
        /** @var Collection<int, Holiday> */
        return $this->query()
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->get();
    }

    public function findByDate(string $date): ?Holiday
    {
        /** @var Holiday|null */
        return $this->query()->whereDate('date', $date)->first();
    }
}
