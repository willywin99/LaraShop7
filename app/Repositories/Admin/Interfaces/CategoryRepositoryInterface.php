<?php

namespace App\Repositories\Admin\Interfaces;

use App\Http\Requests\CategoryRequest;
interface CategoryRepositoryInterface
{
    public function paginate(int $perPage);

    public function findById(int $categoryId);

    public function getCategoryDropdown(int $exceptId = null);

    // public function create(CategoryRequest $categoryRequest);
    public function create($params);

    // public function update(CategoryRequest $categoryRequest, int $categoryId);
    public function update($params, int $categoryId);

    public function delete(int $categoryId);
}
