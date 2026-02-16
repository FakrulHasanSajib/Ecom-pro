<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SliderController extends Controller
{
    /**
     * PUBLIC API: ল্যান্ডিং পেজের জন্য একটিভ স্লাইডারগুলো দিবে
     */
    public function getActiveSliders()
    {
        $sliders = Slider::where('status', true)
            ->orderBy('serial', 'asc')
            ->get()
            ->map(function ($slider) {
                return [
                    'id' => $slider->id,
                    'title' => $slider->title,
                    'description' => $slider->short_description,
                    'image_url' => asset('storage/' . $slider->image),
                    'link' => $slider->link
                ];
            });

        return response()->json($sliders);
    }

    /**
     * ADMIN API: সব স্লাইডার দেখা (List)
     */
    public function index()
    {
        return response()->json(Slider::orderBy('serial', 'asc')->get());
    }

    /**
     * ADMIN API: নতুন স্লাইডার তৈরি করা
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'title' => 'nullable|string|max:255',
            'serial' => 'integer',
            'link' => 'nullable|url'
        ]);

        $path = $request->file('image')->store('sliders', 'public');

        $slider = Slider::create([
            'title' => $request->title,
            'short_description' => $request->short_description,
            'image' => $path,
            'link' => $request->link,
            'serial' => $request->serial ?? 0,
            'status' => $request->status ?? true,
        ]);

        return response()->json(['message' => 'Slider created successfully', 'data' => $slider]);
    }

    /**
     * ADMIN API: স্লাইডার আপডেট করা
     */
    public function update(Request $request, $id)
    {
        $slider = Slider::findOrFail($id);

        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'title' => 'nullable|string',
        ]);

        $data = $request->all();

        // নতুন ইমেজ থাকলে আগেরটা ডিলিট করে নতুনটা আপলোড
        if ($request->hasFile('image')) {
            if ($slider->image && Storage::disk('public')->exists($slider->image)) {
                Storage::disk('public')->delete($slider->image);
            }
            $data['image'] = $request->file('image')->store('sliders', 'public');
        }

        $slider->update($data);

        return response()->json(['message' => 'Slider updated successfully', 'data' => $slider]);
    }

    /**
     * ADMIN API: স্লাইডার ডিলিট করা
     */
    public function destroy($id)
    {
        $slider = Slider::findOrFail($id);

        if ($slider->image && Storage::disk('public')->exists($slider->image)) {
            Storage::disk('public')->delete($slider->image);
        }

        $slider->delete();

        return response()->json(['message' => 'Slider deleted successfully']);
    }
}
