<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    // ১. গ্যালারির সব ছবি লোড করা
    public function index()
    {
        $files = Storage::disk('public')->files('uploads');
        $images = [];
        foreach ($files as $file) {
            $images[] = [
                'path' => asset('storage/' . $file),
                'name' => basename($file)
            ];
        }
        // নতুন ছবি আগে দেখাবে
        return response()->json(array_reverse($images));
    }

    // ২. ছবি আপলোড করা
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();

            // পাবলিক স্টোরেজে সেভ
            $path = $file->storeAs('uploads', $filename, 'public');

            return response()->json([
                'message' => 'Image uploaded successfully',
                'url' => asset('storage/' . $path)
            ]);
        }

        return response()->json(['message' => 'Upload failed'], 400);
    }
}
