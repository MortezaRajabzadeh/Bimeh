<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * ایجاد نمونه مدل
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * دریافت همه رکوردها
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->all($columns);
    }

    /**
     * دریافت همه رکوردها به صورت صفحه‌بندی شده
     */
    public function paginate(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return $this->model->paginate($perPage, $columns);
    }

    /**
     * ایجاد رکورد جدید
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * آپدیت رکورد
     */
    public function update(array $data, $id, string $attribute = 'id'): bool
    {
        return $this->model->where($attribute, $id)->update($data);
    }

    /**
     * آپدیت یا ایجاد
     */
    public function updateOrCreate(array $attributes, array $values = []): Model
    {
        return $this->model->updateOrCreate($attributes, $values);
    }

    /**
     * یافتن رکورد با شناسه
     */
    public function find($id, array $columns = ['*']): ?Model
    {
        return $this->model->find($id, $columns);
    }

    /**
     * یافتن یا خطا
     */
    public function findOrFail($id, array $columns = ['*']): Model
    {
        return $this->model->findOrFail($id, $columns);
    }

    /**
     * یافتن با فیلد دلخواه
     */
    public function findBy(string $field, $value, array $columns = ['*']): ?Model
    {
        return $this->model->where($field, $value)->first($columns);
    }

    /**
     * تعداد رکوردها
     */
    public function count(): int
    {
        return $this->model->count();
    }

    /**
     * حذف با شناسه
     */
    public function delete($id): bool
    {
        return $this->model->destroy($id);
    }

    /**
     * جستجو با معیارهای متفاوت
     */
    public function findWhere(array $criteria, array $columns = ['*']): Collection
    {
        $query = $this->model->newQuery();

        foreach ($criteria as $key => $value) {
            if (is_array($value)) {
                list($field, $operator, $search) = $value;
                $query->where($field, $operator, $search);
            } else {
                $query->where($key, '=', $value);
            }
        }

        return $query->get($columns);
    }
} 