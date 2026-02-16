<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use App\Http\Resources\CategoryResource;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    // ১. সব ক্যাটাগরি দেখার জন্য API
 public function index()
{
    // সব ক্যাটাগরি গেট করুন
    $categories = \App\Models\Category::all();

    // সরাসরি ডাটা রিটার্ন করুন (Inertia::render করবেন না এখানে)
    return response()->json($categories);
}

    // ২. নতুন ক্যাটাগরি তৈরি করার API
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean'
        ]);

        $category = $this->categoryService->createCategory($validated);
        return new CategoryResource($category);
    }

    // ৩. ক্যাটাগরি আপডেট করার API
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,'.$category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean'
        ]);

        $updatedCategory = $this->categoryService->updateCategory($category, $validated);
        return new CategoryResource($updatedCategory);
    }

    // ৪. ক্যাটাগরি ডিলিট করার API
    public function destroy(Category $category)
    {
        $this->categoryService->deleteCategory($category);
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
