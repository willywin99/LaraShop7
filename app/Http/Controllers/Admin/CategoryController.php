<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;

// use App\Models\Category;

// use Str;
use Session;

use App\Authorizable;

use App\Repositories\Admin\Interfaces\CategoryRepositoryInterface;

class CategoryController extends Controller
{
    use Authorizable;

    private $categoryRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository) {
        parent::__construct();

        $this->categoryRepository = $categoryRepository;

        $this->data['currentAdminMenu'] = 'catalog';
        $this->data['currentAdminSubMenu'] = 'category';
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $this->data['categories'] = Category::orderBy('name', 'ASC')->paginate(10);
        $this->data['categories'] = $this->categoryRepository->paginate(10);

        return view('admin.categories.index', $this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->data['categories'] = $this->categoryRepository->getCategoryDropdown();
        // $this->data['category'] = null;

        return view('admin.categories.form', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CategoryRequest $request)
    {
        // $params = $request->except('_token');
        // $params['slug'] = Str::slug($params['name']);
        // $params['parent_id'] = (int)$params['parent_id'];

        if ($this->categoryRepository->create($request)) {
            Session::flash('success', 'Category has been saved');
        }
        return redirect('admin/categories');
    }

    // /**
    //  * Display the specified resource.
    //  *
    //  * @param  int  $id
    //  * @return \Illuminate\Http\Response
    //  */
    // public function show($id)
    // {
    //     //
    // }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // $category = Category::findOrFail($id);
        // $category = $this->categoryRepository->findById($id);
        // $categories = Category::where('id', '!=', $id)->orderBy('name', 'asc')->get();
        // $categories = $this->categoryRepository->getCategoryDropdown($id);

        // $this->data['categories'] = $categories->toArray();
        $this->data['categories'] = $this->categoryRepository->getCategoryDropdown($id);
        $this->data['category'] = $this->categoryRepository->findById($id);

        return view('admin.categories.form', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CategoryRequest $request, $id)
    {
        // $params = $request->except('_token');
        // $params['slug'] = Str::slug($params['name']);
        // $params['parent_id'] = (int)$params['parent_id'];

        // $category = Category::findOrFail($id);
        if ($this->categoryRepository->update($request, $id)) {
            Session::flash('success', 'Category has been updated.');
        }

        return redirect('admin/categories');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // $category  = Category::findOrFail($id);

        if ($this->categoryRepository->delete($id)) {
            Session::flash('success', 'Category has been deleted');
        }

        return redirect('admin/categories');
    }
}
