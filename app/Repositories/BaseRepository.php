<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Layer 5 base — concrete repositories extend this and only add
 * domain-specific query methods on top. Services MUST go through a
 * repository; they MUST NOT call Eloquent statically. The point of
 * the abstraction is to enforce the layer, not to add features.
 */
abstract class BaseRepository
{
    /**
     * Fully-qualified Eloquent model class name.
     *
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    public function query(): Builder
    {
        return ($this->model())::query();
    }

    public function find(int $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function findOrFail(int $id): Model
    {
        return $this->query()->findOrFail($id);
    }

    /**
     * @return Collection<int, Model>
     */
    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function paginate(int $perPage = 25): LengthAwarePaginator
    {
        return $this->query()->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Model
    {
        return ($this->model())::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data): Model
    {
        $model->update($data);

        return $model->fresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    public function restore(Model $model): bool
    {
        if (! method_exists($model, 'restore')) {
            return false;
        }

        return (bool) $model->restore();
    }
}
