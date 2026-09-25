<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Rules\PasswordRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\Customer\CustomerAuthService;

class ProfileController extends Controller
{
    public function __construct(private CustomerAuthService $authService)
    {
    }
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

        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone' => 'required|string|max:20',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:20480',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Update name if provided
        if ($request->has('name') && $request->input('name')) {
            $user->name = $request->input('name');
            Log::info('Updating name', ['new_name' => $user->name]);
        }

        // Update phone if provided
        if ($request->has('phone')) {
            $user->phone = $request->input('phone');
            Log::info('Updating phone', ['new_phone' => $user->phone]);
        }

        // Handle avatar upload
        // Handle avatar upload or removal
        if ($request->hasFile('avatar')) {
            Log::info('Avatar file detected');
            $user->deleteAvatarFile();

            // Store new avatar
            $path = $request->file('avatar')->store('avatars', 'public');
            Log::info('Avatar stored', ['path' => $path]);

            $user->avatar_url = $path;
        } elseif ($request->boolean('remove_avatar') || $request->input('remove_avatar') === '1' || $request->input('remove_avatar') === 'true') {
            Log::info('Removing avatar for customer user', ['user_id' => $user->id]);
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

    public function sendDeleteAccountOtp(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $expiresAt = $this->authService->sendDeleteAccountOtp($user, $request->password);

        return response()->json([
            'message' => 'Verification code sent to your email to confirm account deletion.',
            'expires_at' => $expiresAt,
        ]);
    }

    /**
     * Delete user account entirely
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        // Verify OTP
        $this->authService->verifyDeleteAccountOtp($user, $request->otp);

        // Revoke all tokens
        $user->tokens()->delete();

        // Set user to inactive and start 90-day grace period
        $user->is_active = false;
        $user->account_deletion_requested_at = now();
        $user->save();

        return response()->json(['message' => 'Account deactivated and scheduled for deletion in 90 days.']);
    }

    public function sendVerifyCurrentEmailOtp(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();
        $expiresAt = $this->authService->sendVerifyCurrentEmailOtp($user);

        return response()->json([
            'message' => 'Verification code sent to your current email.',
            'expires_at' => $expiresAt,
        ]);
    }

    public function verifyCurrentEmailOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();
        $this->authService->verifyCurrentEmailOtp($user, $request->otp);

        return response()->json([
            'message' => 'Current email verified successfully.',
        ]);
    }

    public function sendChangeEmailOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255|unique:users,email',
        ]);

        $expiresAt = $this->authService->sendChangeEmailOtp($request->user(), $request->email);

        return response()->json([
            'message' => 'Verification code sent to your new email.',
            'expires_at' => $expiresAt,
        ]);
    }

    public function verifyChangeEmailOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
        ]);

        $this->authService->verifyChangeEmailOtp($request->user(), $request->email, $request->otp);

        return response()->json([
            'message' => 'Email updated successfully.',
            'user' => $request->user(),
        ]);
    }

    public function sendChangePasswordOtp(Request $request)
    {
        $expiresAt = $this->authService->sendChangePasswordOtp($request->user());

        return response()->json([
            'message' => 'Verification code sent to your current email.',
            'expires_at' => $expiresAt,
        ]);
    }

    public function verifyChangePasswordOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|size:6',
        ]);

        $this->authService->verifyChangePasswordOtp($request->user(), $request->otp);

        return response()->json([
            'message' => 'OTP verified successfully.',
        ]);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', new PasswordRule()],
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $this->authService->updatePassword($user, $request->password);

        return response()->json([
            'message' => 'Password updated successfully.',
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'purpose' => 'required|string|in:verify_current_email,change_email,change_password,delete_account',
        ]);

        /** @var \App\Models\User $user */
        $user = $request->user();

        $expiresAt = $this->authService->resendProfileOtp($user, $request->purpose);

        return response()->json([
            'message' => 'Verification code resent successfully.',
            'expires_at' => $expiresAt,
        ]);
    }
}
