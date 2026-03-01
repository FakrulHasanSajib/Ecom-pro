<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\BannerCategory;
use Illuminate\Http\Request;

class BannerCategoryController extends Controller
{
    public function index()
    {
        return response()->json(BannerCategory::orderBy('id', 'desc')->get());
    }

    public function getActive()
    {
        // এই এপিআইটা Banner পেজে ড্রপডাউনের জন্য কাজে লাগবে
        return response()->json(BannerCategory::where('status', 1)->get());
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        BannerCategory::create(['name' => $request->name, 'status' => $request->status ?? 1]);
        return response()->json(['message' => 'Category created successfully']);
    }

    public function toggleStatus($id)
    {
        $category = BannerCategory::findOrFail($id);
        $category->status = !$category->status;
        $category->save();
        return response()->json(['message' => 'Status updated']);
    }

    public function destroy($id)
    {
        BannerCategory::findOrFail($id)->delete();
        return response()->json(['message' => 'Category deleted']);
    }
}
