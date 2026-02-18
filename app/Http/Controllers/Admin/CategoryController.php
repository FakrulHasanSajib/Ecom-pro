<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * ১. ক্যাটাগরি লিস্ট (API)
     * URL: GET /api/admin/categories
     */
    public function index(): JsonResponse
    {
        // ক্যাটাগরিগুলো লেটেস্ট অনুযায়ী নিয়ে আসা
        $categories = Category::with('parent')->latest()->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ], 200);
    }

    /**
     * ২. নতুন ক্যাটাগরি তৈরি করা (API)
     * URL: POST /api/admin/categories
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255|unique:categories',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean'
        ]);

        try {
            // সার্ভিস ব্যবহার করে ক্যাটাগরি তৈরি
            $category = $this->categoryService->createCategory($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Category created successfully!',
                'data'    => $category
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Failed to create category: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ৩. ক্যাটাগরি আপডেট করা (API)
     * URL: PUT/PATCH /api/admin/categories/{category}
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255|unique:categories,name,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean'
        ]);

        try {
            $updatedCategory = $this->categoryService->updateCategory($category, $validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Category updated successfully!',
                'data'    => $updatedCategory
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ৪. ক্যাটাগরি ডিলিট করা (API)
     * URL: DELETE /api/admin/categories/{category}
     */
    public function destroy(Category $category): JsonResponse
    {
        try {
            $this->categoryService->deleteCategory($category);

            return response()->json([
                'status'  => 'success',
                'message' => 'Category deleted successfully!'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
