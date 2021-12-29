<?php

namespace App\Repositories\Admin;

// use App\Http\Requests\CategoryRequest;

use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Repositories\Admin\Interfaces\CategoryRepositoryInterface;

use App\Models\Category;

use App\Exceptions\CreateCategoryErrorException;
use App\Exceptions\UpdateCategoryErrorException;
use App\Exceptions\CategoryNotFoundErrorException;

class CategoryRepository implements CategoryRepositoryInterface
{
    private $model;

    public function __construct(Category $model)
    {
        $this->_model = $model;
    }

    public function paginate($perPage)
    {
        return Category::orderBy('name', 'ASC')->paginate($perPage);
    }

    public function findById($categoryId)
    {
        // return Category::findOrFail($categoryId);
        try {
            return Category::findOrFail($categoryId);
        } catch (ModelNotFoundException $e) {
            throw new CategoryNotFoundErrorException('Category not found');
        }
    }

    public function getCategoryDropdown($exceptCategoryId = null)
    {
        $categories = new Category;

        if ($exceptCategoryId) {
            $categories = $categories->where('id', '!=', $exceptCategoryId);
        }

        $categories = $categories->orderBy('name', 'asc');

        return $categories->get();
    }

    // public function create(CategoryRequest $request)
    public function create($params)
    {
        // $params = $request->except('_token');
        // $params['slug'] = \Str::slug($params['name']);
        $params['slug'] = isset($params['name']) ? Str::slug($params['name']) : null;

        if (!isset($params['parent_id'])) {
            $params['parent_id'] = 0;
        }

        // $params['parent_id'] = (int)$params['parent_id'];

        try {
            return $this->_model::create($params);
        } catch (QueryException $e) {
            throw new CreateCategoryErrorException('Error on creating a category');
        }
        // return $this->model::create($params);
    }

    public function update($params, $id)
    {
        // $params = $request->except('_token');
        // $params['slug'] = \Str::slug($params['name']);
        // $params['parent_id'] = (int)$params['parent_id'];

        $params['slug'] = isset($params['name']) ? Str::slug($params['name']) : null;

        if (!isset($params['parent_id'])) {
            $params['parent_id'] = 0;
        }

        $category = Category::findOrFail($id);

        try {
            return $category->update($params);
        } catch (QueryException $e) {
            throw new UpdateCategoryErrorException('Error on updating a category');
        }

        // return $category->update($params);
    }

    public function delete($categoryId)
    {
        $category = Category::findOrFail($categoryId);

        return $category->delete();
    }
}
