<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Color;
use Illuminate\Http\Request;

class ColorController extends Controller
{
    public function index() { return response()->json(Color::orderBy('id', 'desc')->get()); }
    public function getActive() { return response()->json(Color::where('status', 1)->get()); }

    public function store(Request $request) {
        $request->validate(['name' => 'required|string', 'code' => 'required|string']);
        Color::create(['name' => $request->name, 'code' => $request->code, 'status' => $request->status ?? 1]);
        return response()->json(['message' => 'Color created']);
    }

    public function toggleStatus($id) {
        $item = Color::findOrFail($id); $item->status = !$item->status; $item->save();
        return response()->json(['message' => 'Status updated']);
    }

    public function destroy($id) { Color::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
