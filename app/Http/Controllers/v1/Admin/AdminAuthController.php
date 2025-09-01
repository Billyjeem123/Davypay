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
        $credentials = $request->validated();
        $admin = Admin::where('email', $credentials['email'])->first();

        if (!$admin || !Hash::check($credentials['password'], $admin->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if (!$admin->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'Account is deactivated. Contact support.',
            ], 403);
        }

        Auth::guard('admin')->login($admin, true); // Add remember parameter
        $request->session()->regenerate();
        if (!Auth::guard('admin')->check()) {
            return response()->json([
                'status' => false,
                'message' => 'Login failed. Please try again.',
            ], 500);
        }
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
