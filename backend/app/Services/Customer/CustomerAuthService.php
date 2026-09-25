<?php

namespace App\Services\Customer;

use App\Models\CustomerOtpVerification;
use App\Models\User;
use App\Mail\CustomerRegistrationOtpMail;
use App\Mail\CustomerLoginOtpMail;
use App\Mail\CustomerPasswordResetOtpMail;
use App\Mail\CustomerChangeEmailOtpMail;
use App\Mail\CustomerVerifyCurrentEmailOtpMail;
use App\Mail\CustomerChangePasswordOtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole;

class CustomerAuthService
{
    private const MAX_ATTEMPTS = 5;
    private const OTP_LIFETIME_MINUTES = 5;

    private function hasRecentOtp(string $email, string $purpose): bool
    {
        $lastOtp = CustomerOtpVerification::withTrashed()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->orderByDesc('created_at')
            ->first();

        if ($lastOtp) {
            // If the record was soft-deleted (due to 5 failed attempts)
            if ($lastOtp->trashed()) {
                if (now()->isBefore($lastOtp->expires_at)) {
                    // Penalty period is still active
                    $remaining = abs(now()->diffInSeconds($lastOtp->expires_at));
                    throw ValidationException::withMessages(['email' => ["Verification locked due to too many failed attempts. Please try again later.|{$remaining}"]]);
                } else {
                    // Penalty period expired, allow sending a new OTP
                    return false;
                }
            }

            // Normal cooldown is 300 seconds (5 minutes) for active OTPs
            if (abs(now()->diffInSeconds($lastOtp->created_at)) < 300) {
                return true;
            }
        }
        
        return false;
    }

    public function sendRegistrationOtp(array $data): ?string
    {
        cache()->put('register_' . $data['email'], $data, now()->addMinutes(self::OTP_LIFETIME_MINUTES));

        if ($this->hasRecentOtp($data['email'], 'register')) {
            // Return existing OTP expiration so frontend can sync
            $existing = CustomerOtpVerification::where('email', $data['email'])
                ->where('purpose', 'register')
                ->whereNull('verified_at')
                ->orderByDesc('created_at')
                ->first();
            return $existing?->expires_at?->toIso8601String();
        }

        $otp = $this->generateOtp();
        Mail::to($data['email'])->send(new CustomerRegistrationOtpMail($otp, $data['name']));
        $record = $this->storeOtp($data['email'], $otp, 'register');

        return $record->expires_at->toIso8601String();
    }

    public function verifyRegistrationOtp(string $email, string $otp)
    {
        $this->verifyOtp($email, $otp, 'register');

        $data = cache()->get('register_' . $email);
        if (!$data) {
            throw ValidationException::withMessages(['email' => ['Registration data expired. Please try again.']]);
        }

        return DB::transaction(function () use ($data, $email) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'role' => 'customer',
            ]);

            $user->email_verified_at = now();
            $user->save();

            cache()->forget('register_' . $email);

            // Notify all admins and superadmins about the new customer registration
            \App\Services\NotificationService::notifyAdmins(
                new \App\Notifications\NewCustomerNotification($user)
            );

            return $user;
        });
    }

    public function checkLoginCredentials(string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (!$user || !in_array($user->role, ['customer', UserRole::Customer], true) || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        if (isset($user->is_active) && !$user->is_active) {
            if ($user->account_deletion_requested_at && $user->account_deletion_requested_at >= now()->subDays(90)) {
                // Allow login to proceed for account recovery
            } else {
                throw ValidationException::withMessages([
                    'email' => ['Your account is inactive. Please contact an administrator.']
                ]);
            }
        }

        return $user;
    }

    public function sendLoginOtp(User $user): ?string
    {
        if ($this->hasRecentOtp($user->email, 'login')) {
            // Return existing OTP expiration so frontend can sync
            $existing = CustomerOtpVerification::where('email', $user->email)
                ->where('purpose', 'login')
                ->whereNull('verified_at')
                ->orderByDesc('created_at')
                ->first();
            return $existing?->expires_at?->toIso8601String();
        }

        $otp = $this->generateOtp();
        Mail::to($user->email)->send(new CustomerLoginOtpMail($otp, $user->name));
        $record = $this->storeOtp($user->email, $otp, 'login');

        return $record->expires_at->toIso8601String();
    }

    public function verifyLoginOtp(string $email, string $otp)
    {
        $this->verifyOtp($email, $otp, 'login');

        $user = User::where('email', $email)->first();

        // Restore account if it was pending deletion
        if (!$user->is_active && $user->account_deletion_requested_at) {
            $user->is_active = true;
            $user->account_deletion_requested_at = null;
            $user->save();
        }

        // Notification for login
        \App\Services\NotificationService::notifyAdmins(new \App\Notifications\UserLoginNotification($user));

        return $user;
    }

    public function sendPasswordResetOtp(string $email): ?string
    {
        if ($this->hasRecentOtp($email, 'reset_password')) {
            // Return existing OTP expiration so frontend can sync
            $existing = CustomerOtpVerification::where('email', $email)
                ->where('purpose', 'reset_password')
                ->whereNull('verified_at')
                ->orderByDesc('created_at')
                ->first();
            return $existing?->expires_at?->toIso8601String();
        }

        $user = User::where('email', $email)->whereIn('role', ['customer', UserRole::Customer])->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['User not found with this email address.']
            ]);
        }

        $otp = $this->generateOtp();
        Mail::to($email)->send(new CustomerPasswordResetOtpMail($otp, $user->name));
        $record = $this->storeOtp($email, $otp, 'reset_password');
        return $record->expires_at->toIso8601String();
    }

    public function verifyPasswordResetOtp(string $email, string $otp)
    {
        $this->verifyOtp($email, $otp, 'reset_password');

        // Create a temporary reset token
        $resetToken = \Illuminate\Support\Str::random(64);
        cache()->put('reset_token_' . $email, $resetToken, now()->addMinutes(15));

        return $resetToken;
    }

    public function resetPassword(string $email, string $resetToken, string $newPassword)
    {
        $cachedToken = cache()->get('reset_token_' . $email);

        if (!$cachedToken || $cachedToken !== $resetToken) {
            throw ValidationException::withMessages([
                'reset_token' => ['Invalid or expired reset token.']
            ]);
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            $user->password = Hash::make($newPassword);
            $user->save();
            // Invalidate all tokens
            $user->tokens()->delete();
        }

        cache()->forget('reset_token_' . $email);
    }

    public function resendOtp(string $email, string $purpose): ?string
    {
        if ($purpose === 'register') {
            $data = cache()->get('register_' . $email);
            if (!$data) {
                throw ValidationException::withMessages(['email' => ['Registration data expired. Please register again.']]);
            }
            return $this->sendRegistrationOtp($data);
        } elseif ($purpose === 'login') {
            $user = User::where('email', $email)->first();
            if (!$user) {
                throw ValidationException::withMessages(['email' => ['User not found.']]);
            }
            return $this->sendLoginOtp($user);
        } elseif ($purpose === 'reset_password') {
            return $this->sendPasswordResetOtp($email);
        }

        return null;
    }

    public function resendProfileOtp(User $user, string $purpose): ?string
    {
        if ($purpose === 'verify_current_email') {
            return $this->sendVerifyCurrentEmailOtp($user);
        } elseif ($purpose === 'change_email') {
            $newEmail = cache()->get('change_email_' . $user->id);
            if (!$newEmail) {
                throw ValidationException::withMessages(['email' => ['Email change request expired. Please start over.']]);
            }
            return $this->sendChangeEmailOtp($user, $newEmail);
        } elseif ($purpose === 'change_password') {
            return $this->sendChangePasswordOtp($user);
        } elseif ($purpose === 'delete_account') {
            if ($this->hasRecentOtp($user->email, 'delete_account')) {
                $existing = CustomerOtpVerification::where('email', $user->email)
                    ->where('purpose', 'delete_account')
                    ->whereNull('verified_at')
                    ->orderByDesc('created_at')
                    ->first();
                return $existing?->expires_at?->toIso8601String();
            }

            $otp = $this->generateOtp();
            Mail::to($user->email)->send(new \App\Mail\CustomerDeleteAccountOtpMail($otp, $user->name));
            $record = $this->storeOtp($user->email, $otp, 'delete_account');
            return $record->expires_at->toIso8601String();
        }

        return null;
    }

    public function getOtpStatus(string $email, string $purpose): ?string
    {
        $lastOtp = CustomerOtpVerification::withTrashed()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->orderByDesc('created_at')
            ->first();

        if ($lastOtp && now()->isBefore($lastOtp->expires_at)) {
            return $lastOtp->expires_at->toIso8601String();
        }

        return null;
    }

    public function sendVerifyCurrentEmailOtp(User $user): ?string
    {
        if ($this->hasRecentOtp($user->email, 'verify_current_email')) {
            $existing = CustomerOtpVerification::where('email', $user->email)
                ->where('purpose', 'verify_current_email')
                ->whereNull('verified_at')
                ->orderByDesc('created_at')
                ->first();
            return $existing?->expires_at?->toIso8601String();
        }

        $otp = $this->generateOtp();
        Mail::to($user->email)->send(new CustomerVerifyCurrentEmailOtpMail($otp, $user->name));
        $record = $this->storeOtp($user->email, $otp, 'verify_current_email');

        return $record->expires_at->toIso8601String();
    }

    public function verifyCurrentEmailOtp(User $user, string $otp)
    {
        $this->verifyOtp($user->email, $otp, 'verify_current_email');
        
        // Store authorization token for 15 minutes
        cache()->put('change_email_authorized_' . $user->id, true, now()->addMinutes(15));
    }

    public function sendChangeEmailOtp(User $user, string $newEmail): ?string
    {
        if (!cache()->get('change_email_authorized_' . $user->id)) {
            throw ValidationException::withMessages(['email' => ['Please verify your current email first.']]);
        }

        cache()->put('change_email_' . $user->id, $newEmail, now()->addMinutes(self::OTP_LIFETIME_MINUTES));

        if ($this->hasRecentOtp($newEmail, 'change_email')) {
            $existing = CustomerOtpVerification::where('email', $newEmail)
                ->where('purpose', 'change_email')
                ->whereNull('verified_at')
                ->orderByDesc('created_at')
                ->first();
            return $existing?->expires_at?->toIso8601String();
        }

        $otp = $this->generateOtp();
        Mail::to($newEmail)->send(new CustomerChangeEmailOtpMail($otp, $user->name));
        $record = $this->storeOtp($newEmail, $otp, 'change_email');

        return $record->expires_at->toIso8601String();
    }

    public function verifyChangeEmailOtp(User $user, string $newEmail, string $otp)
    {
        if (!cache()->get('change_email_authorized_' . $user->id)) {
            throw ValidationException::withMessages(['email' => ['Please verify your current email first.']]);
        }

        $this->verifyOtp($newEmail, $otp, 'change_email');
        
        $cachedEmail = cache()->get('change_email_' . $user->id);
        if (!$cachedEmail || $cachedEmail !== $newEmail) {
            throw ValidationException::withMessages(['email' => ['Email change request expired or invalid. Please try again.']]);
        }

        $user->email = $newEmail;
        $user->save();
        
        cache()->forget('change_email_' . $user->id);
        cache()->forget('change_email_authorized_' . $user->id);
    }

    public function sendChangePasswordOtp(User $user): ?string
    {
        if ($this->hasRecentOtp($user->email, 'change_password')) {
            $existing = CustomerOtpVerification::where('email', $user->email)
                ->where('purpose', 'change_password')
                ->whereNull('verified_at')
                ->orderByDesc('created_at')
                ->first();
            return $existing?->expires_at?->toIso8601String();
        }

        $otp = $this->generateOtp();
        Mail::to($user->email)->send(new CustomerChangePasswordOtpMail($otp, $user->name));
        $record = $this->storeOtp($user->email, $otp, 'change_password');

        return $record->expires_at->toIso8601String();
    }

    public function verifyChangePasswordOtp(User $user, string $otp)
    {
        $this->verifyOtp($user->email, $otp, 'change_password');
        
        // Store authorization token for 15 minutes
        cache()->put('change_password_authorized_' . $user->id, true, now()->addMinutes(15));
    }

    public function updatePassword(User $user, string $newPassword)
    {
        if (!cache()->get('change_password_authorized_' . $user->id)) {
            throw ValidationException::withMessages(['password' => ['Please verify your OTP first.']]);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        cache()->forget('change_password_authorized_' . $user->id);
    }

    public function sendDeleteAccountOtp(User $user, string $password): ?string
    {
        if (!Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['password' => ['Incorrect password.']]);
        }

        if ($this->hasRecentOtp($user->email, 'delete_account')) {
            $existing = CustomerOtpVerification::where('email', $user->email)
                ->where('purpose', 'delete_account')
                ->whereNull('verified_at')
                ->orderByDesc('created_at')
                ->first();
            return $existing?->expires_at?->toIso8601String();
        }

        $otp = $this->generateOtp();
        Mail::to($user->email)->send(new \App\Mail\CustomerDeleteAccountOtpMail($otp, $user->name));
        $record = $this->storeOtp($user->email, $otp, 'delete_account');

        return $record->expires_at->toIso8601String();
    }

    public function verifyDeleteAccountOtp(User $user, string $otp)
    {
        $this->verifyOtp($user->email, $otp, 'delete_account');
    }

    private function generateOtp(): string
    {
        return str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function storeOtp(string $email, string $otp, string $purpose): CustomerOtpVerification
    {
        // Delete previous unverified OTPs for this purpose
        CustomerOtpVerification::where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->delete();

        return CustomerOtpVerification::create([
            'email' => $email,
            'otp_hash' => Hash::make($otp),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(self::OTP_LIFETIME_MINUTES),
        ]);
    }

    private function verifyOtp(string $email, string $otp, string $purpose)
    {
        $record = CustomerOtpVerification::withTrashed()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->orderByDesc('created_at')
            ->first();

        if (!$record) {
            throw ValidationException::withMessages(['otp' => ['Invalid verification code.']]);
        }

        if ($record->trashed()) {
            if (now()->isBefore($record->expires_at)) {
                $remaining = abs(now()->diffInSeconds($record->expires_at));
                throw ValidationException::withMessages(['otp' => ["Too many failed attempts. Please try again later.|{$remaining}"]]);
            }
            throw ValidationException::withMessages(['otp' => ['This code is no longer valid. Please request a new code.']]);
        }

        if (now()->isAfter($record->expires_at)) {
            throw ValidationException::withMessages(['otp' => ['The verification code has expired.']]);
        }

        $record->attempts = $record->attempts + 1;

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            $record->save();
            $record->delete(); // Soft delete it to lock it
            $remaining = abs(now()->diffInSeconds($record->expires_at));
            throw ValidationException::withMessages(['otp' => ["Too many failed attempts. Please try again later.|{$remaining}"]]);
        }

        if (!Hash::check($otp, $record->otp_hash)) {
            $record->save();
            throw ValidationException::withMessages(['otp' => ['Invalid verification code.']]);
        }

        // Mark as verified
        $record->verified_at = now();
        $record->save();
    }
}
