<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\Auth\RegisterRequest;
use App\Http\Requests\Customer\Auth\LoginRequest;
use App\Http\Requests\Customer\Auth\VerifyOtpRequest;
use App\Http\Requests\Customer\Auth\ResetPasswordRequest;
use App\Http\Requests\Customer\Auth\SendOtpRequest;
use App\Models\User;
use App\Services\Customer\CustomerAuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private CustomerAuthService $authService)
    {
    }

    public function sendRegisterOtp(RegisterRequest $request)
    {
        $expiresAt = $this->authService->sendRegistrationOtp($request->validated());
        return response()->json([
            'message' => 'Verification code sent to your email.',
            'expires_at' => $expiresAt,
        ]);
    }

    public function verifyRegisterOtp(VerifyOtpRequest $request)
    {
        $user = $this->authService->verifyRegistrationOtp($request->email, $request->otp);
        $token = $user->createToken('customer_auth_token', ['*'], $this->getTokenExpiration($user))->plainTextToken;

        return response()->json([
            'message' => 'Account created successfully.',
            'token' => $token,
            'user' => $user->load(['addresses', 'orders']),
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = $this->authService->checkLoginCredentials($request->email, $request->password);
        $expiresAt = $this->authService->sendLoginOtp($user);

        return response()->json([
            'requires_otp' => true,
            'message' => 'OTP has been sent to your email.',
            'expires_at' => $expiresAt,
        ]);
    }

    public function verifyLoginOtp(VerifyOtpRequest $request)
    {
        $user = $this->authService->verifyLoginOtp($request->email, $request->otp);
        $token = $user->createToken('customer_auth_token', ['*'], $this->getTokenExpiration($user))->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => $user->load(['addresses', 'orders']),
        ]);
    }

    public function sendForgotPasswordOtp(SendOtpRequest $request)
    {
        $expiresAt = $this->authService->sendPasswordResetOtp($request->email);
        
        return response()->json([
            'message' => 'Verification code has been sent to your email.',
            'expires_at' => $expiresAt,
        ]);
    }

    public function resendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'purpose' => 'required|in:login,register,reset_password',
        ]);

        $expiresAt = $this->authService->resendOtp($request->email, $request->purpose);

        return response()->json([
            'message' => 'Verification code resent successfully.',
            'expires_at' => $expiresAt,
        ]);
    }

    public function verifyForgotPasswordOtp(VerifyOtpRequest $request)
    {
        $resetToken = $this->authService->verifyPasswordResetOtp($request->email, $request->otp);
        
        return response()->json([
            'message' => 'OTP verified successfully.',
            'reset_token' => $resetToken
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $this->authService->resetPassword($request->email, $request->reset_token, $request->password);
        
        return response()->json([
            'message' => 'Password reset successfully. You can now login with your new password.'
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        return $user->load(['addresses', 'orders']);
    }

    public function otpStatus(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'purpose' => 'required|string|in:login,register,reset_password',
        ]);

        $expiresAt = $this->authService->getOtpStatus($request->email, $request->purpose);

        return response()->json([
            'expires_at' => $expiresAt
        ]);
    }

    private function getTokenExpiration(User $user)
    {
        return now()->addDays(30);
    }
}
