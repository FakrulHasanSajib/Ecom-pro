<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    public function get($key, $default = null)
    {
        // Cache for 24 hours
        $settings = Cache::remember('app_settings', 60 * 60 * 24, function () {
            return Setting::all()->pluck('value', 'key');
        });

        return $settings[$key] ?? $default;
    }

    public function update(array $data)
    {
        foreach ($data as $key => $value) {
            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                // ১. যদি ইমেজ ফাইল হয়
                if ($setting->type === 'image' && request()->hasFile($key)) {
                    // আগের ইমেজ ডিলিট করা (যদি থাকে)
                    if ($setting->value) {
                        Storage::disk('public')->delete($setting->value);
                    }
                    // নতুন ইমেজ আপলোড
                    $path = request()->file($key)->store('settings', 'public');
                    $setting->value = $path;
                }
                // ২. যদি সাধারণ টেক্সট বা পাসওয়ার্ড হয়
                else {
                    $setting->value = $value;
                }

                $setting->save();
            }
        }

        // Cache ক্লিয়ার করা যাতে ফ্রন্টএন্ডে সাথে সাথে আপডেট হয়
        Cache::forget('app_settings');
    }
}
