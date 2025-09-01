<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Http\Requests\GlobalRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AdminAuthController extends Controller
{
    public function LoginAdmin(AdminLoginRequest $request): JsonResponse
    {
        \Log::info('Login attempt', ['email' => $request->email]);

        // Get validated credentials
        $credentials = $request->validated();

        // Find admin user
        $admin = Admin::where('email', $credentials['email'])->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            \Log::warning('Invalid login attempt', ['email' => $credentials['email']]);
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Check if admin is active
        if (!$admin->is_active) {
            \Log::warning('Inactive admin login attempt', ['email' => $credentials['email']]);
            return response()->json([
                'status' => false,
                'message' => 'Account is deactivated. Contact support.',
            ], 403);
        }

        // Log in the admin using the admin guard
        Auth::guard('admin')->login($admin, true); // Add remember parameter

        // Regenerate session to prevent session fixation
        $request->session()->regenerate();

        // Verify the login worked
        if (!Auth::guard('admin')->check()) {
            \Log::error('Admin login failed after Auth::login', ['email' => $credentials['email']]);
            return response()->json([
                'status' => false,
                'message' => 'Login failed. Please try again.',
            ], 500);
        }

        \Log::info('Admin login successful', [
            'email' => $credentials['email'],
            'admin_id' => $admin->id,
            'session_id' => session()->getId()
        ]);

        // Update last login timestamp
        $admin->update(['last_login_at' => now()]);

        return response()->json([
            'status' => true,
            'message' => 'Login successful.',
            'redirect' => route('admin.home'),
            'user' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
                'role' => $admin->role ?? 'admin'
            ],
        ]);
    }
}
