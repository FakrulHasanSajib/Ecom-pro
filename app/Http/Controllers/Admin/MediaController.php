<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media; // ডাটাবেস মডেল যুক্ত করা হলো
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


class MediaController extends Controller
{
    // ১. গ্যালারির সব ছবি ডাটাবেস থেকে লোড করা
    public function index()
    {
        $media = Media::latest()->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'file_name' => $item->file_name,
                'file_path' => $item->file_path,
                'file_url' => asset('storage/' . $item->file_path)
            ];
        });

        return response()->json(['data' => $media]);
    }

    // ২. ছবি আপলোড ও ডাটাবেসে সেভ করা
   public function store(Request $request)
{
    // ১. নিশ্চিত করুন এখানে 'file' লেখা আছে
    $request->validate([
        'file' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
    ]);

    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs('uploads', $filename, 'public');

        // ২. ডাটাবেসে সেভ (যদি Media মডেল থাকে)
        $media = \App\Models\Media::create([
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
        ]);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'data' => $media
        ]);
    }

    return response()->json(['message' => 'Upload failed'], 400);
}

    // ৩. ছবি ডিলিট করা (নতুন যুক্ত করা হলো)
    public function destroy($id)
    {
        $media = Media::find($id);

        if ($media) {
            // স্টোরেজ ফোল্ডার থেকে আসল ফাইলটি রিমুভ করা
            if (Storage::disk('public')->exists($media->file_path)) {
                Storage::disk('public')->delete($media->file_path);
            }

            // ডাটাবেস থেকে রেকর্ড ডিলিট করা
            $media->delete();

            return response()->json(['message' => 'Image deleted successfully']);
        }

        return response()->json(['message' => 'Image not found'], 404);
    }
}
