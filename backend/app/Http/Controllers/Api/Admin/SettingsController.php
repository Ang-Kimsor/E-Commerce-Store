<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    /**
     * Get all settings (Public - no auth required)
     */
    public function index()
    {
        $settings = SiteSetting::all();

        $settings = $settings->map(function ($setting) {
            if ($setting->type === 'image' && $setting->value) {
                $path = ltrim($setting->value, '/');
                $setting->full_url = '/api/storage/' . $path;

                $timestamp = strtotime($setting->updated_at);
                $setting->full_url .= '?t=' . $timestamp;
            }
            return $setting;
        });

        return response()->json($settings);
    }
    public function show(string $key)
    {
        $setting = SiteSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        if ($setting->type === 'image' && $setting->value) {
            $path = ltrim($setting->value, '/');
            $setting->full_url = '/api/storage/' . $path;

            $timestamp = strtotime($setting->updated_at);
            $setting->full_url .= '?t=' . $timestamp;
        }

        return response()->json($setting);
    }

    /**
     * Bulk update settings by section with validation
     */
    public function update(Request $request)
    {

        $rules = [
            'site_name' => 'sometimes|required|string|max:255',
            'site_logo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'site_favicon' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg,ico,webp|max:1024',
            'site_description' => 'sometimes|nullable|string',
            'copyright_text' => 'sometimes|required|string|max:255',

            'contact_email' => 'sometimes|required|email|max:255',
            'contact_phone' => 'sometimes|required|string|max:100',
            'contact_address' => 'sometimes|required|string',
            'google_map_url' => 'sometimes|nullable|url',

            'maintenance_mode' => 'sometimes|required',
            'maintenance_message' => 'required_if:maintenance_mode,true,1,"true"',
            'registration_enabled' => 'sometimes|required',

            'seo_title' => 'sometimes|required|string|max:255',
            'seo_description' => 'sometimes|nullable|string',
            'seo_keywords' => 'sometimes|nullable|string',

            'email_from_name' => 'sometimes|required|string|max:255',
            'email_from_address' => 'sometimes|required|email|max:255',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Map each known key to its type so we can create it if it doesn't exist yet
        $typeMap = [
            'site_logo'             => 'image',
            'site_favicon'          => 'image',
            'site_name'             => 'text',
            'site_description'      => 'textarea',
            'copyright_text'        => 'text',
            'contact_email'         => 'text',
            'contact_phone'         => 'text',
            'contact_address'       => 'textarea',
            'google_map_url'        => 'text',
            'maintenance_mode'      => 'boolean',
            'maintenance_message'   => 'textarea',
            'registration_enabled'  => 'boolean',
            'seo_title'             => 'text',
            'seo_description'       => 'textarea',
            'seo_keywords'          => 'textarea',
            'email_from_name'       => 'text',
            'email_from_address'    => 'text',
        ];

        $allData = $request->all();
        $files = $request->allFiles();
        $updatedSettings = [];

        // Combine normal inputs and files
        $keysToUpdate = array_unique(array_merge(array_keys($allData), array_keys($files)));

        foreach ($keysToUpdate as $key) {
            if ($key === '_method') {
                continue;
            }

            $type = $typeMap[$key] ?? 'text';

            // Find existing or prepare a new instance
            $setting = SiteSetting::firstOrNew(['key' => $key]);

            // Set type and a default description if this is a brand-new row
            if (!$setting->exists) {
                $setting->type = $type;
                $setting->description = ucwords(str_replace('_', ' ', $key));
            }

            if ($setting->type === 'image' && $request->hasFile($key)) {
                // Delete old image if exists
                if ($setting->value) {
                    Storage::disk('public')->delete($setting->value);
                }
                $path = $request->file($key)->store('settings', 'public');
                $setting->value = $path;
                $setting->save();
            } elseif ($setting->type !== 'image' && array_key_exists($key, $allData)) {
                $val = $request->input($key);
                if ($setting->type === 'boolean') {
                    $setting->value = (filter_var($val, FILTER_VALIDATE_BOOLEAN) || $val === '1' || $val === 1 || $val === 'true' || $val === true) ? 'true' : 'false';
                } else {
                    $setting->value = $val !== null ? (string)$val : '';
                }
                $setting->save();
            }

            if ($setting->type === 'image' && $setting->value) {
                $path = ltrim($setting->value, '/');
                $setting->full_url = '/api/storage/' . $path . '?t=' . strtotime($setting->updated_at);
            }

            $updatedSettings[] = $setting;
        }

        SiteSetting::clearCache();

        return response()->json([
            'message' => 'Settings updated successfully',
            'settings' => $updatedSettings,
        ]);
    }

    public function deleteImage(string $key)
    {
        $setting = SiteSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json(['message' => 'Setting not found'], 404);
        }

        if ($setting->type !== 'image') {
            return response()->json(['message' => 'This setting is not an image'], 400);
        }

        if ($setting->value) {
            Storage::disk('public')->delete($setting->value);
            $setting->value = null;
            $setting->save();
            SiteSetting::clearCache();
        }

        return response()->json(['message' => 'Image deleted successfully']);
    }
}
