<?php

use App\Services\SettingService;
use Illuminate\Support\Facades\Storage;

if (!function_exists('get_setting')) {
    /**
     * Get setting value by key
     * Usage: get_setting('site_name', 'My Shop')
     */
    function get_setting($key, $default = null)
    {
        try {
            return app(SettingService::class)->get($key, $default);
        } catch (\Exception $e) {
            return $default;
        }
    }
}

if (!function_exists('get_setting_image')) {
    /**
     * Get full image URL for settings (Logo, Favicon)
     * Usage: <img src="{{ get_setting_image('site_logo') }}">
     */
    function get_setting_image($key, $default = null)
    {
        $path = get_setting($key);

        if ($path && Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        // ডিফল্ট ইমেজ না দিলে এবং ভ্যালু না থাকলে ফাঁকা স্ট্রিং বা প্লেসহোল্ডার
        return $default ? asset($default) : asset('images/default-placeholder.png');
    }
}
