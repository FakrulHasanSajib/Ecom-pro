<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Str; // 🔥 এটি যুক্ত করা হলো

class BrandController extends Controller
{
    public function index() { return response()->json(Brand::orderBy('id', 'desc')->get()); }
    public function getActive() { return response()->json(Brand::where('status', 1)->get()); }

    public function store(Request $request) {
        $request->validate(['name' => 'required|string']);

        // 🔥 নামের ওপর ভিত্তি করে অটোমেটিক স্লাগ তৈরি হবে
        Brand::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name), // e.g., "Bangla" -> "bangla"
            'status' => $request->status ?? 1
        ]);

        return response()->json(['message' => 'Brand created']);
    }

    public function toggleStatus($id) {
        $item = Brand::findOrFail($id); $item->status = !$item->status; $item->save();
        return response()->json(['message' => 'Status updated']);
    }

    public function destroy($id) { Brand::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
