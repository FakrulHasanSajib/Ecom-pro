<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    public function getActiveSliders()
    {
        $sliders = Slider::where('status', true)->orderBy('serial', 'asc')->get()->map(function ($slider) {
            return [
                'id' => $slider->id,
                'title' => $slider->title,
                'category_name' => $slider->category_name,
                'description' => $slider->short_description,
                'image_url' => asset('storage/' . $slider->image),
                'link' => $slider->link
            ];
        });
        return response()->json($sliders);
    }

    public function index()
    {
        $sliders = Slider::orderBy('id', 'desc')->get()->map(function ($slider) {
            $slider->image_url = asset('storage/' . $slider->image);
            return $slider;
        });
        return response()->json($sliders);
    }

    public function store(Request $request)
    {
        // গ্যালারি থেকে সরাসরি ইমেজের পাথ আসবে, তাই image কে string হিসেবে ভ্যালিডেট করা হলো
        $request->validate([
            'image' => 'required|string',
            'category_name' => 'required|string',
            'title' => 'nullable|string',
            'link' => 'nullable|url'
        ]);

        Slider::create([
            'category_name' => $request->category_name,
            'title' => $request->title,
            'link' => $request->link,
            'image' => $request->image, // গ্যালারির ইমেজ পাথ সরাসরি সেভ হবে
            'status' => $request->status == 'true' || $request->status == 1 ? 1 : 0,
        ]);

        return response()->json(['message' => 'Banner created successfully']);
    }

    public function toggleStatus($id)
    {
        $slider = Slider::findOrFail($id);
        $slider->status = !$slider->status;
        $slider->save();
        return response()->json(['message' => 'Status updated']);
    }

    public function destroy($id)
    {
        $slider = Slider::findOrFail($id);
        // গ্যালারির ছবি ডিলিট করব না, শুধু স্লাইডার ডিলিট করব
        $slider->delete();
        return response()->json(['message' => 'Banner deleted successfully']);
    }
}
