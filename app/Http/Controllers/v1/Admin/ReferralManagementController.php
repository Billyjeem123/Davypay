<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\Settings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralManagementController extends Controller
{

    public function index()
    {
        $totalReferrals = Referral::count();

        $successfulReferralsLast7Days = Referral::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7))
            ->count();

        // Get top referrer by number of successful referrals in the last 7 days
        $topReferrer = Referral::select('referrer_id', DB::raw('count(*) as total'))
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('referrer_id')
            ->orderByDesc('total')
            ->with('referrer') // eager load user
            ->first();

        // Count distinct referrer users who have made at least 1 referral
        $activeReferrers = Referral::select('referrer_id')
            ->groupBy('referrer_id')
            ->count();

        // Get referrers list (for the table)
        $referrers = User::whereIn('id', Referral::pluck('referrer_id')->unique())
            ->with(['referralsMade' => function ($query) {
                $query->latest();
            }])
            ->get();


        $users = User::with(['referral.referrer'])->latest()->get();

        return view('dashboard.referral.index', [
            'totalReferrals' => $totalReferrals,
            'successfulReferralsLast7Days' => $successfulReferralsLast7Days,
            'topReferrer' => $topReferrer?->referrer, // top referrer user
            'activeReferrers' => $activeReferrers,
            'referrers' => $referrers,
            'users' => $users,
        ]);
    }


    public function details($referrerId)
    {
        $referrer = User::findOrFail($referrerId);

        $referrals = Referral::with('referred')
            ->where('referrer_id', $referrerId)
            ->latest()
            ->get();
        return view('dashboard.referral.details', compact('referrer', 'referrals'));
    }


    public function saveReferralFee(Request $request)
    {
        try {
            $validated = $request->validate([
                'setting_value' => 'required|numeric|min:0',
                'referral_status' => 'required|in:active,inactive',
            ]);

            // Save the setting
            Settings::set('referral_fee', $request->setting_value);
            Settings::set('referral_status', $request->referral_status);

            return back()->with('success', 'Referral fee updated successfully.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Please check the form for errors.');

        } catch (\Throwable $e) {
            Log::error('Failed to save referral fee: ' . $e->getMessage(), [
                'setting_value' => $request->setting_value ?? 'unknown',
                'exception' => $e,
            ]);

            return back()
                ->withInput()
                ->with('error', 'Something went wrong. Please try again.');
        }
    }

}
