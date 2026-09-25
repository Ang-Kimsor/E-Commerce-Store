<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Rules\PasswordRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    /**
     * Get current user's profile
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Update current user's profile
     */
    public function update(Request $request)
    {
        $user = $request->user();

        Log::info('Profile update request', [
            'user_id' => $user->id,
            'has_avatar' => $request->hasFile('avatar'),
            'has_name' => $request->has('name'),
            'has_phone' => $request->has('phone'),
            'all_inputs' => $request->all(),
        ]);

        $isAdmin = $user->role === \App\Enums\UserRole::Admin;

        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'telegram_id' => ($isAdmin ? 'required' : 'nullable') . '|string|max:255|unique:users,telegram_id,' . $user->id,
            'password' => ['nullable', 'string', new PasswordRule()],
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
        ], [
            'telegram_id.required' => 'Telegram User ID is required.',
            'telegram_id.unique' => 'This Telegram ID is already registered.',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update name if provided
        if ($request->has('name') && $request->input('name')) {
            $user->name = $request->input('name');
            Log::info('Updating name', ['new_name' => $user->name]);
        }

        // Update email if provided
        if ($request->has('email') && $request->input('email')) {
            $user->email = $request->input('email');
            Log::info('Updating email', ['new_email' => $user->email]);
        }

        // Update phone if provided
        if ($request->has('phone')) {
            $user->phone = $request->input('phone');
            Log::info('Updating phone', ['new_phone' => $user->phone]);
        }

        // Update telegram id if provided
        if ($request->has('telegram_id')) {
            $tid = trim((string) $request->input('telegram_id'));
            $user->telegram_id = $tid !== '' ? $tid : null;
        }

        // Update password if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
            Log::info('Password updated for user', ['user_id' => $user->id]);
        }

        // Handle avatar upload or removal
        if ($request->hasFile('avatar')) {
            Log::info('Avatar file detected');
            $user->deleteAvatarFile();

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            Log::info('Avatar stored', ['path' => $path]);

            $user->avatar_url = $path;
        } elseif ($request->boolean('remove_avatar') || $request->input('remove_avatar') === '1' || $request->input('remove_avatar') === 'true') {
            Log::info('Removing avatar for user', ['user_id' => $user->id]);
            $user->deleteAvatarFile();
            $user->avatar_url = null;
        }

        $user->save();
        Log::info('Profile updated successfully', ['user' => $user->toArray()]);

        // Add full URL for avatar
        $userData = $user->toArray();
        if ($user->avatar_url) {
            $userData['avatar_full_url'] = Storage::url($user->avatar_url);
        }

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $userData,
        ]);
    }

    /**
     * Delete user's avatar
     */
    public function deleteAvatar(Request $request)
    {
        $user = $request->user();
        $user->deleteAvatarFile();
        $user->avatar_url = null;
        $user->save();

        return response()->json(['message' => 'Avatar deleted successfully']);
    }
}
