<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    protected $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    /**
     * সব সেটিং গ্রুপ অনুযায়ী পাঠানো (যেমন: General, Payment, Social)
     */
    public function index()
    {
        $settings = Setting::all()->groupBy('group');

        // ইমেজগুলোর জন্য ফুল URL জেনারেট করা
        $formattedSettings = $settings->map(function ($groupItems) {
            return $groupItems->map(function ($item) {
                if ($item->type === 'image' && $item->value) {
                    $item->value_url = asset('storage/' . $item->value);
                }
                return $item;
            });
        });

        return response()->json([
            'status' => 'success',
            'data' => $formattedSettings
        ]);
    }

    /**
     * সেটিং আপডেট করা
     */
    public function update(Request $request)
    {
        // _token বা _method বাদ দিয়ে বাকি সব ডাটা সার্ভিসে পাঠানো
        $data = $request->except(['_token', '_method']);

        try {
            $this->settingService->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Settings updated successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update settings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
