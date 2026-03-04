<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingService
{
    public function get($key, $default = null)
    {
        $settings = Cache::rememberForever('app_settings', function () {
            return Setting::all()->pluck('value', 'key');
        });

        return $settings[$key] ?? $default;
    }

    public function update(array $data)
    {
        $groupMap = [
            'primary_color' => 'appearance', 'secondary_color' => 'appearance',
            'header_bg' => 'appearance', 'button_radius' => 'appearance',
            'site_name' => 'general', 'site_description' => 'general',
            'phone' => 'contact', 'email' => 'contact', 'address' => 'contact',
            'facebook' => 'social', 'instagram' => 'social', 'twitter' => 'social',
            'linkedin' => 'social', 'youtube' => 'social', 'whatsapp' => 'social'
        ];

        foreach ($data as $key => $value) {
            // ডাটাবেসে আগে থেকে আছে কি না চেক করা
            $setting = Setting::where('key', $key)->first();

            // 🔥 যদি ডাটাবেসে না থাকে এবং ইউজার ইনপুটও ফাঁকা দেয়, তবে নতুন করে সেভ করবে না (Skip)
            if (!$setting && ($value === null || $value === '')) {
                continue;
            }

            // যদি ডাটাবেসে না থাকে কিন্তু ইউজার ইনপুট দেয়, তবে নতুন তৈরি করবে
            if (!$setting) {
                $setting = new Setting();
                $setting->key = $key;
            }

            // ইমেজ হ্যান্ডলিং
            if (request()->hasFile($key)) {
                if ($setting->value && Storage::disk('public')->exists($setting->value)) {
                    Storage::disk('public')->delete($setting->value);
                }
                $setting->value = request()->file($key)->store('settings', 'public');
                $setting->type = 'image';
            } else {
                $setting->value = $value;
                $setting->type = $setting->type ?? 'text';
            }

            // গ্রুপ সেট করা
            $setting->group = $groupMap[$key] ?? 'general';
            $setting->save();
        }

        Cache::forget('app_settings');
    }
}
