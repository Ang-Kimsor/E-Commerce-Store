<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SiteSetting;

class SiteSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key' => 'site_logo', 'value' => null, 'type' => 'image', 'description' => 'Main logo displayed in header'],
            ['key' => 'site_name', 'value' => 'Unknown Site', 'type' => 'text', 'description' => 'Website name'],
            ['key' => 'site_favicon', 'value' => null, 'type' => 'image', 'description' => 'Browser favicon'],
            ['key' => 'site_description', 'value' => 'Unknown Site', 'type' => 'textarea', 'description' => 'Short website description'],
            ['key' => 'copyright_text', 'value' => '© ' . date('Y') . ' Unknown Site. All rights reserved.', 'type' => 'text', 'description' => 'Footer copyright text'],

            // Contact
            ['key' => 'contact_email', 'value' => 'info@unknown.com', 'type' => 'text', 'description' => 'Contact email address'],
            ['key' => 'contact_phone', 'value' => '+855 12 345 678', 'type' => 'text', 'description' => 'Contact phone number'],
            ['key' => 'contact_address', 'value' => 'Phnom Penh, Cambodia', 'type' => 'textarea', 'description' => 'Company physical address'],
            ['key' => 'google_map_url', 'value' => 'https://maps.google.com', 'type' => 'text', 'description' => 'Google Maps location URL'],

            // System
            ['key' => 'maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'description' => 'Enable or disable maintenance mode'],
            ['key' => 'maintenance_message', 'value' => 'We are currently performing routine maintenance. We will be back online shortly!', 'type' => 'textarea', 'description' => 'Message displayed on the maintenance page'],
            ['key' => 'registration_enabled', 'value' => 'true', 'type' => 'boolean', 'description' => 'Allow customer self-registration'],

            // SEO
            ['key' => 'seo_title', 'value' => 'Unknown Site - Quality Online Shopping', 'type' => 'text', 'description' => 'Default SEO title'],
            ['key' => 'seo_description', 'value' => 'Shop top quality products online with Unknown Site.', 'type' => 'textarea', 'description' => 'Default SEO description'],
            ['key' => 'seo_keywords', 'value' => 'unknown, site, ecommerce, online shop, cambodia', 'type' => 'textarea', 'description' => 'Default SEO keywords'],

            // Email
            ['key' => 'email_from_name', 'value' => 'Unknown Site', 'type' => 'text', 'description' => 'Outgoing email sender name'],
            ['key' => 'email_from_address', 'value' => 'no-reply@unknown.com', 'type' => 'text', 'description' => 'Outgoing email sender address'],
        ];

        foreach ($settings as $setting) {
            SiteSetting::query()->firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
