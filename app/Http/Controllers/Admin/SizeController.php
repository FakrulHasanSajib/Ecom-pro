<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    public function index() { return response()->json(Size::orderBy('id', 'desc')->get()); }
    public function getActive() { return response()->json(Size::where('status', 1)->get()); }

    public function store(Request $request) {
        $request->validate(['name' => 'required|string']);
        Size::create(['name' => $request->name, 'status' => $request->status ?? 1]);
        return response()->json(['message' => 'Size created']);
    }

    public function toggleStatus($id) {
        $item = Size::findOrFail($id); $item->status = !$item->status; $item->save();
        return response()->json(['message' => 'Status updated']);
    }

    public function destroy($id) { Size::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
