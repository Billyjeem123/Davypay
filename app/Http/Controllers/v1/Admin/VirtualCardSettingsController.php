<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VirtualCardSettingsController extends Controller
{
    /**
     * Display the virtual card settings page
     */
    public function index()
    {
        return view('dashboard.card.virtual-card.main');
    }

    /**
     * Store or update virtual card settings
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'setting_type' => 'required|string|in:virtual_card_topup_fee,virtual_card_creation_fee,virtual_card_account_fee,dollar_conversion_rate',
                'setting_value' => 'required|numeric|min:0',
            ], [
                'setting_type.required' => 'Please select a setting type.',
                'setting_type.in' => 'Invalid setting type selected.',
                'setting_value.required' => 'Please enter a value.',
                'setting_value.numeric' => 'The value must be a number.',
                'setting_value.min' => 'The value must be at least 0.',
            ]);

            // Save the setting
            Settings::set($validated['setting_type'], $validated['setting_value']);

            // Get friendly name for success message
            $settingNames = [
                'virtual_card_topup_fee' => 'Virtual Card Top Up Fee',
                'virtual_card_creation_fee' => 'Virtual Card Creation Fee',
                'virtual_card_account_fee' => 'Virtual Card Account Fee',
                'dollar_conversion_rate' => 'Dollar Conversion Rate',
            ];

            $settingName = $settingNames[$validated['setting_type']] ?? 'Setting';

            return back()->with('success', "{$settingName} updated successfully.");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please check the form for errors.');

        } catch (\Throwable $e) {
            Log::error('Failed to save virtual card setting: ' . $e->getMessage(), [
                'setting_type' => $request->setting_type ?? 'unknown',
                'setting_value' => $request->setting_value ?? 'unknown',
                'exception' => $e,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Something went wrong. Please try again.');
        }
    }

    /**
     * Get a specific setting value (API endpoint)
     */
    public function getSetting($settingType)
    {
        try {
            $allowedSettings = [
                'virtual_card_topup_fee',
                'virtual_card_creation_fee',
                'virtual_card_account_fee',
                'dollar_conversion_rate',
            ];

            if (!in_array($settingType, $allowedSettings)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid setting type.',
                ], 400);
            }

            $value = Settings::get($settingType, 0);

            return response()->json([
                'success' => true,
                'setting_type' => $settingType,
                'value' => $value,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to retrieve setting: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve setting.',
            ], 500);
        }
    }

    /**
     * Get all virtual card settings (API endpoint)
     */
    public function getAllSettings()
    {
        try {
            $settings = [
                'virtual_card_topup_fee' => Settings::get('virtual_card_topup_fee', 0),
                'virtual_card_creation_fee' => Settings::get('virtual_card_creation_fee', 0),
                'virtual_card_account_fee' => Settings::get('virtual_card_account_fee', 0),
                'dollar_conversion_rate' => Settings::get('dollar_conversion_rate', 0),
            ];

            return response()->json([
                'success' => true,
                'settings' => $settings,
            ]);

        } catch (\Throwable $e) {
            Log::error('Failed to retrieve all settings: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve settings.',
            ], 500);
        }
    }


}
