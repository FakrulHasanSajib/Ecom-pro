<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // 1. General & Branding
            ['group' => 'general', 'key' => 'site_name', 'value' => 'My Shop', 'type' => 'text'],
            ['group' => 'general', 'key' => 'site_logo', 'value' => null, 'type' => 'image'],
            ['group' => 'general', 'key' => 'site_favicon', 'value' => null, 'type' => 'image'],
            ['group' => 'general', 'key' => 'header_phone', 'value' => '+8801700000000', 'type' => 'text'],
            ['group' => 'general', 'key' => 'footer_text', 'value' => '© 2026 All rights reserved.', 'type' => 'text'],

            // 2. Appearance (Color)
            ['group' => 'appearance', 'key' => 'primary_color', 'value' => '#FF5733', 'type' => 'color'],
            ['group' => 'appearance', 'key' => 'secondary_color', 'value' => '#333333', 'type' => 'color'],

            // 3. Payment Gateway (SSLCommerz)
            ['group' => 'payment', 'key' => 'ssl_store_id', 'value' => 'testbox', 'type' => 'text'],
            ['group' => 'payment', 'key' => 'ssl_store_password', 'value' => 'qwerty', 'type' => 'password'],
            ['group' => 'payment', 'key' => 'ssl_sandbox_mode', 'value' => '1', 'type' => 'boolean'],

            // 4. Tracking Pixels (API Keys)
            ['group' => 'tracking', 'key' => 'fb_pixel_id', 'value' => '', 'type' => 'text'],
            ['group' => 'tracking', 'key' => 'fb_access_token', 'value' => '', 'type' => 'password'], // CAPI Token
            ['group' => 'tracking', 'key' => 'google_analytics_id', 'value' => '', 'type' => 'text'],
            ['group' => 'tracking', 'key' => 'tiktok_pixel_id', 'value' => '', 'type' => 'text'],

            // 5. SMS Gateway
            ['group' => 'sms', 'key' => 'sms_provider', 'value' => 'sslwireless', 'type' => 'text'], // sslwireless, mim, etc.
            ['group' => 'sms', 'key' => 'sms_api_key', 'value' => '', 'type' => 'password'],
            ['group' => 'sms', 'key' => 'sms_sender_id', 'value' => '', 'type' => 'text'],

            // 6. Social Media Links
            ['group' => 'social', 'key' => 'social_facebook', 'value' => 'https://facebook.com', 'type' => 'text'],
            ['group' => 'social', 'key' => 'social_youtube', 'value' => 'https://youtube.com', 'type' => 'text'],
            ['group' => 'social', 'key' => 'social_instagram', 'value' => 'https://instagram.com', 'type' => 'text'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
