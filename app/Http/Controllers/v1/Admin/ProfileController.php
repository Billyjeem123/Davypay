<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Show the profile page
     */
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        return view('dashboard.settings.index', compact('admin'));
    }

    /**
     * Update admin profile details
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        try {
            $admin = Auth::guard('admin')->user();

            // Update admin profile with validated data
            $admin->update([
                'first_name' => $request->fullName,
                'email' => $request->email,
                'company_name' => $request->companyName,
                'phone_number' => $request->phoneNo,
            ]);

            return redirect()->back()->with('success', 'Profile updated successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to update profile. Please try again.')
                ->withInput();
        }
    }

    /**
     * Change admin password
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $admin = Auth::guard('admin')->user();

            $validated = $request->validated();

            // Update password
            $admin->update([
                'password' => Hash::make($validated['newPass']),
            ]);

            return redirect()->back()->with('success', 'Password changed successfully!');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to change password. Please try again.');
        }
    }

}
