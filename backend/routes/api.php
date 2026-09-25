<?php

use App\Http\Controllers\Api\Customer\AddressController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Customer\CategoryController as CustomerCategoryController;
use App\Http\Controllers\Api\Admin\AddressController as AdminAddressController;
use App\Http\Controllers\Api\Admin\AdminController;
use App\Http\Controllers\Api\Admin\CustomerController;
use App\Http\Controllers\Api\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\Customer\OrderController as CustomerOrderController;
use App\Http\Controllers\Api\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Api\Admin\StockMovementController;
use App\Http\Controllers\Api\Customer\ProductController as CustomerProductController;

use App\Http\Controllers\Api\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Api\Admin\SettingsController;
use App\Http\Controllers\Api\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Api\Admin\ProfileController as AdminProfileController;

use App\Http\Controllers\Api\Admin\NotificationController;
use App\Http\Controllers\Api\Admin\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

use App\Http\Controllers\Api\Customer\AuthController as CustomerAuthController;

// Customer OTP Authentication Routes
Route::prefix('customer/auth')->middleware('throttle:60,1')->group(function () {
    // Customer Register
    Route::prefix('register')->group(function () {
        Route::post('send-otp', [CustomerAuthController::class, 'sendRegisterOtp']);
        Route::post('verify-otp', [CustomerAuthController::class, 'verifyRegisterOtp']);
    });

    // Customer Login
    Route::prefix('login')->group(function () {
        Route::post('/', [CustomerAuthController::class, 'login']);
        Route::post('verify-otp', [CustomerAuthController::class, 'verifyLoginOtp']);
    });

    // Customer Forget Password
    Route::prefix('forgot-password')->group(function () {
        Route::post('send-otp', [CustomerAuthController::class, 'sendForgotPasswordOtp']);
        Route::post('verify-otp', [CustomerAuthController::class, 'verifyForgotPasswordOtp']);
    });

    // Customer Reset Password
    Route::post('reset-password', [CustomerAuthController::class, 'resetPassword']);
    // Customer resend otp
    Route::post('resend-otp', [CustomerAuthController::class, 'resendOtp']);
    // Customer otp status (cooldown status & active otp)
    Route::get('otp-status', [CustomerAuthController::class, 'otpStatus']);
});

// Customer Authentication Routes
Route::prefix('customer/auth')->middleware(['auth:sanctum', 'customer'])->group(function () {
    Route::post('logout', [CustomerAuthController::class, 'logout']);
    Route::get('me', [CustomerAuthController::class, 'me']);
});

// Admin and SuperAdmin Authentication Routes
Route::prefix('admin/auth')->group(function () {
    Route::post('login', [AdminAuthController::class, 'loginAdmin']);
    Route::post('logout', [AdminAuthController::class, 'logout'])->middleware('auth:sanctum');
});

// Public product and category routes (for customers)
Route::apiResource('products', CustomerProductController::class)->only(['index', 'show']);
Route::apiResource('categories', CustomerCategoryController::class)->only(['index']);

// Public settings routes (for customers)
Route::get('settings', [SettingsController::class, 'index']);
Route::get('settings/{key}', [SettingsController::class, 'show']);

// Serve storage files (images, etc.)
Route::get('storage/{path}', function ($path) {
    try {
        $filePath = storage_path('app/public/' . $path);

        Log::info('Storage file request', [
            'requested_path' => $path,
            'full_path' => $filePath,
            'exists' => file_exists($filePath),
        ]);

        if (!file_exists($filePath)) {
            Log::error('Storage file not found', ['path' => $filePath]);
            abort(404, 'File not found');
        }

        // Robust mime type detection
        $mimeType = 'application/octet-stream';
        try {
            if (function_exists('mime_content_type')) {
                $mimeType = @mime_content_type($filePath);
            }
        } catch (Throwable $e) {
        }

        // Fallback for common types if mime_content_type failed or returned generic
        if ($mimeType === 'application/octet-stream' || !$mimeType) {
            $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $map = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml'
            ];
            $mimeType = $map[$extension] ?? 'application/octet-stream';
        }

        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    } catch (\Exception $e) {
        Log::error('Storage file serve error', [
            'path' => $path,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        abort(500, 'Error serving file: ' . $e->getMessage());
    }
})->where('path', '.*');


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('me', function (Request $request) {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
        return $user->load(['addresses', 'orders']);
    });

    Route::post('logout', function (Request $request) {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        return response()->noContent();
    });

    // Customer-only routes
    Route::middleware(['customer'])->group(function () {
        // Prefixed /customer routes matching specification
        Route::prefix('customer')->group(function () {

            // Customer Profile Management
            Route::controller(CustomerProfileController::class)->prefix('profile')->group(function () {
                Route::get('/', 'show');
                Route::post('/', 'update');
                Route::delete('avatar', 'deleteAvatar');
                Route::prefix('delete-account')->group(function () {
                    Route::post('send-otp', 'sendDeleteAccountOtp');
                    Route::delete('verify', 'deleteAccount');
                });

                // Profile Updates via OTP
                Route::prefix('change-email')->group(function () {
                    Route::post('send-current-otp', 'sendVerifyCurrentEmailOtp');
                    Route::post('verify-current-otp', 'verifyCurrentEmailOtp');
                    Route::post('send-otp', 'sendChangeEmailOtp');
                    Route::post('verify', 'verifyChangeEmailOtp');
                });

                Route::prefix('change-password')->group(function () {
                    Route::post('send-otp', 'sendChangePasswordOtp');
                    Route::post('verify', 'verifyChangePasswordOtp');
                    Route::post('update', 'updatePassword');
                });
                Route::post('resend-otp', 'resendOtp');
            });

            // Customer Address Book
            Route::apiResource('addresses', AddressController::class)->except(['show']);

            // Customer Orders & Invoicing
            Route::get('orders/{order}/invoice', [CustomerOrderController::class, 'invoice']);
            Route::apiResource('orders', CustomerOrderController::class)->only(['index', 'store', 'show']);
        });
    });

    // Admin-only routes
    Route::middleware(['admin'])->group(function () {
        Route::prefix('admin')->group(function () {

            // Admin Dashboard & Overview
            Route::get('dashboard', [DashboardController::class, 'index']);

            // Catalog: Categories Management
            Route::prefix('categories')->controller(AdminCategoryController::class)->group(function () {
                Route::get('export', 'export');
                Route::get('{category}/export-detail', 'exportDetail')->withTrashed();
                Route::post('{id}/restore', 'restore');
            });
            Route::apiResource('categories', AdminCategoryController::class)->withTrashed();

            // Catalog: Products & Inventory Management
            Route::prefix('products')->group(function () {
                Route::controller(AdminProductController::class)->group(function () {
                    Route::get('export', 'export');
                    Route::get('{product}/export-detail', 'exportDetail')->withTrashed();
                    Route::post('{id}/restore', 'restore');
                });
            });
            Route::apiResource('products', AdminProductController::class)->withTrashed();
            Route::apiResource('products.stock-movements', StockMovementController::class)->only(['index', 'store']);

            // Orders & Fulfillment Management
            Route::prefix('orders')->controller(AdminOrderController::class)->group(function () {
                Route::get('export', 'export');
                Route::get('{order}/export-detail', 'exportDetail');
                Route::get('{order}/pdf', 'downloadPdf');
            });
            Route::apiResource('orders', AdminOrderController::class)->except(['destroy']);

            // Admin Notifications
            Route::prefix('notifications')->controller(NotificationController::class)->group(function () {
                Route::get('/', 'index');
                Route::post('mark-all-read', 'markAllRead');
                Route::post('{id}/read', 'markAsRead');
            });

            // Customer Accounts Management
            Route::prefix('customers')->controller(CustomerController::class)->group(function () {
                Route::get('export', 'export');
                Route::get('{user}/export-detail', 'exportDetail')->withTrashed();
                Route::post('{id}/restore', 'restore');
                Route::post('{id}/block', 'block');
                Route::post('{id}/unblock', 'unblock');
            });
            Route::apiResource('customers', CustomerController::class)
                ->parameters(['customers' => 'user'])
                ->withTrashed();

            // Customer Address Book Management
            Route::apiResource('addresses', AdminAddressController::class)->only(['store', 'update', 'destroy']);

            // Business Reports & Analytics
            Route::prefix('reports')->controller(ReportController::class)->group(function () {
                Route::get('sales', 'sales');
                Route::get('products', 'products');
                Route::get('customers', 'customers');
                Route::get('inventory', 'inventory');
            });

            // Admin Dropdown Listing Reference
            // Replace here with the except for admin and superadmin
            Route::get('admins', [AdminController::class, 'index']);
        });
    });

    // SuperAdmin-only routes
    Route::middleware(['superadmin'])->group(function () {
        Route::prefix('admin')->group(function () {
            // System Administrators Management
            Route::prefix('admins')->controller(AdminController::class)->group(function () {
                Route::post('{id}/restore', 'restore');
                Route::post('{id}/block', 'block');
                Route::post('{id}/unblock', 'unblock');
            });
            Route::apiResource('admins', AdminController::class)->except(['index'])->withTrashed();
            // Admin/SuperAdmin Profile Management
            Route::controller(AdminProfileController::class)->prefix('profile')->group(function () {
                Route::get('/', 'show');
                Route::post('/', 'update');
                Route::delete('avatar', 'deleteAvatar');
            });

            // Global System Settings Management
            Route::prefix('settings')->controller(SettingsController::class)->group(function () {
                Route::get('/', 'index');
                Route::match(['put', 'post'], 'update', 'update');
                Route::delete('{key}/image', 'deleteImage');
            });
        });
    });
});
