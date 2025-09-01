<?php

namespace App\Http\Controllers\v1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\createUserAdmin;
use App\Http\Requests\GlobalRequest;
use App\Http\Requests\PreferredProviderRequest;
use App\Http\Requests\UpdateTierRequest;
use App\Models\Admin;
use App\Models\FraudCheck;
use App\Models\Settings;
use App\Models\Tier;
use App\Models\TransactionFee;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\Wallet;
use App\Services\VirtualAccountManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function index()
    {
        $totalUsers = User::count();
        $pendingTransactions = TransactionLog::withStatus('pending')->count();
        $completedTransactions = TransactionLog::withStatus('successful')->count();

        $cashInFlow = TransactionLog::totalCashInflow();
        $totalDebit = TransactionLog::totalCashOutflow();
        $internalCredits = TransactionLog::totalInternalCredits(); #  Referrals, refunds, etc.
        $referralSpent = TransactionLog::totalReferralCredits();
        $netBalance = $cashInFlow - $totalDebit;

        $successfulTransfers = TransactionLog::countSuccessfulBankTransfersToday();
        $failedTransfers = TransactionLog::countFailedBankTransfersToday();
        $failedTransferRefunds = TransactionLog::countFailedTransferRefundsToday();
        $todaySuccessfulBillTransaction = TransactionLog::countSuccessfulBillTransactionsToday();
        $usersRegisteredToday = User::registeredTodayCount();


        #  Fraud Stats
        $fraudToday = true; #  toggle for Today or All-Time
        $fraudStats = [
            'date_scope'    => $fraudToday ? 'Today (' . now()->format('jS M Y') . ')' : 'All Time',
            'total_checks'  => FraudCheck::totalChecks($fraudToday),
            'action_stats'  => FraudCheck::actionStats($fraudToday),
            'top_txn_types' => FraudCheck::topFlaggedTransactionTypes($fraudToday),
        ];

        return view('dashboard.index', compact(
            'totalUsers',
            'pendingTransactions',
            'completedTransactions',
            'cashInFlow',
            'totalDebit',
            'internalCredits',
            'referralSpent',
            'netBalance',
            'successfulTransfers',
            'failedTransfers',
            'failedTransferRefunds',
            'todaySuccessfulBillTransaction',
            'fraudStats',
            'usersRegisteredToday'
        ));
    }


    public function userManagement()
    {
        $totalUsers     = User::count();
        $activeUsers    = User::where('is_account_restricted', false)->where('is_ban', false)->count();
        $blockedUsers   = User::banned()->count();
        $recentSignups  = User::recentSignups()->count();
        $restrictedUsers = User::restricted()->count();
        $users = User::dashboardUsers();
        return view('dashboard.user.index', compact(
            'totalUsers',
            'activeUsers',
            'blockedUsers',
            'recentSignups',
            'restrictedUsers',
            'users'
        ));
    }



    public function UserFullInformation($user_id)
    {
        $user = User::with('kyc', 'activity_logs', 'virtual_accounts', 'virtual_cards', 'wallet')->find($user_id);

        if (!$user) {
            abort(404, 'User not found');
        }

        return view('dashboard.user.full-information', compact('user'));
    }

    private function getAllActiveUsers(): array
    {
        $users = User::where('is_account_restricted', false)
            ->where('is_ban', false)
            ->get();

        $count = $users->count();

        return [
            'users' => $users,
            'count' => $count,
        ];
    }

    private function getAllInActiveUsers(): array
    {
        $users = User::where('is_account_restricted', true)->
            orWhere('is_ban', true)
            ->get();

        $count = $users->count();

        return [
            'users' => $users,
            'count' => $count,
        ];
    }

    private function getTierCounts(): array
    {
        return [
            'tier_1' => User::where('account_level', 'tier_1')->count(),
            'tier_2' => User::where('account_level', 'tier_2')->count(),
            'tier_3' => User::where('account_level', 'tier_3')->count(),
            'all_users' =>   $totalUsers = User::count()
        ];
    }




    public function activeUsers(){

        $result = $this->getAllActiveUsers();

        $users = $result['users'];
        $count = $result['count'];
        return view('dashboard.user.active-users', compact('users', 'count'));
    }
    public function suspendedUsers(){

        $result = $this->getAllInActiveUsers();

        $users = $result['users'];
        $count = $result['count'];
        return view('dashboard.user.suspended-users', compact('users', 'count'));
    }

    public function pendingVerificationUsers(){
        return view('dashboard.user.pending-verification-users');
    }

    public function userKyc()
    {
        $tierCounts = $this->getTierCounts();

        #  Get users that have submitted KYC with their KYC data
        $usersWithKyc = User::with('kyc')
            ->whereHas('kyc') #  ensures only users with kyc records
            ->get();

//   return response()->json($usersWithKyc);

        return view('dashboard.kyc.index', compact('tierCounts', 'usersWithKyc'));
    }


    public function usersInTier1()
    {
        $usersWithKyc = User::where('account_level', 'tier_1')
            ->get();

        return view('dashboard.kyc.tier_1', compact( 'usersWithKyc'));
    }


    public function usersInTier2()
    {
        $usersWithKyc = User::with('kyc')->where('account_level', 'tier_2')
            ->get();
//         return response()->json($usersWithKyc);

        return view('dashboard.kyc.tier_2', compact( 'usersWithKyc'));
    }


    public function usersInTier3()
    {
        $usersWithKyc = User::with('kyc')->where('account_level', 'tier_3')
            ->get();


        return view('dashboard.kyc.tier_3', compact( 'usersWithKyc'));
    }

    public function userRoles(){

        $roles = Role::all();
        return view('dashboard.user.roles', compact('roles'));
    }


    public function userPermission(){
        #  Fetch all admin users with their relationships
        $admins = Admin::orderBy('created_at', 'desc')->get();


        $roles = Role::all();

        #  Fetch all available permissions (you'll need to create a Permission model or use a simple array)
        $permissions = Permission::all();
        #  Calculate statistics
        $totalUsers = $admins->count();
        $activeUsers = $admins->where('status', 'active')->count();
        $pendingUsers = $admins->where('status', 'pending')->count();
        $totalRoles = count($roles);

        return view('dashboard.user.assign-permission', compact(
            'admins',
            'roles',
            'permissions',
            'totalUsers',
            'activeUsers',
            'pendingUsers',
            'totalRoles'
        ));
    }


    public function ActivityLogs()
    {
        $metrics = UserActivityLog::metrics();
        $stats = $this->buildActivityStats($metrics);
        $users = $this->getLatestUsers();

        return view('dashboard.user.activity-logs', [
            ...$metrics,
            'stats' => $stats,
            'users' => $users,
        ]);
    }

    private function getLatestUsers()
    {
        return User::select('id', 'first_name', 'last_name', 'email', 'phone', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }



    public function ActivityLogsDetails($id){

        $activityLogs = $this->getUserActivityLogs($id);
        return view('dashboard.user.activity-details', compact('activityLogs'));
    }

    private function getUserActivityLogs($userId) {
        return UserActivityLog::where('user_id', $userId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }






    public function allTransactions(){

        $stats = $this->getAllTransactionStats();

        return view('dashboard.transactions.all-transaction', $stats);
    }


    public function getTransactionFees()
    {
        $fees = TransactionFee::all();
        return view('dashboard.transactions.transaction-fee', compact('fees'));
    }



    private function getAllTransactionStats()
    {
        $transactions = TransactionLog::with('wallet', 'user')
            ->latest()
            ->get();

        return [
            'transactions' => $transactions,
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'pending_count' => $transactions->where('status', 'pending')->count(),
            'failed_count' => $transactions->where('status', 'failed')->count(),
        ];
    }


    private function getAllFailedTransactions()
    {
        $transactions = TransactionLog::with('wallet', 'user')
            ->where('status', 'failed')
            ->latest()
            ->get();

        return [
            'transactions' => $transactions,
            'failed_count' => $transactions->count(), # No need to filter again
        ];
    }

    private function getAllSuccessfulTransactions()
    {
        $transactions = TransactionLog::with('wallet', 'user')
            ->whereIn('status', ['success', 'successful'])
            ->latest()
            ->get();

        $totalCount = $transactions->count();

        $totalToday = $transactions->filter(function ($txn) {
            return $txn->created_at->isToday();
        })->count();

        $averageAmount = $transactions->avg('amount');

        return [
            'transactions'    => $transactions,
            'total_counts'    => $totalCount,
            'total_today'     => $totalToday,
            'average_amount'  => number_format($averageAmount, 2),
        ];
    }



    private function getAllPendingStats()
    {
        $transactions = TransactionLog::with('wallet', 'user')
            ->where('status', 'pending')
            ->latest()
            ->get();

        return [
            'transactions' => $transactions,
            'total_transactions' => $transactions->count(),
        ];
    }

    public function pendingTransactions()
    {
        $stats = $this->getAllPendingStats(); # Get the pending stats

        return view('dashboard.transactions.pending-transactions', $stats);
    }


    public function reportTransactions(){
        return view('dashboard.transactions.transactions-report');
    }

    public function walletOverview()
    {
        $total_wallet_count = Wallet::count();
        $total_amount_wallet = $this->moneyInWallet();
        $total_money_locked = Wallet::moneyInsideLockedWallet();
        $total_locked_amount = Wallet::totalLockedWallet();
        $usersWithWallets = $this->loadUsersWithWallets();

        return view('dashboard.wallet.wallet-report', compact(
            'total_wallet_count',
            'total_amount_wallet',
            'total_money_locked',
            'total_locked_amount',
            'usersWithWallets',
        ));
    }


    public  function moneyInWallet()
    {
        return Wallet::MoneyInsideWallet();
    }


    private function loadUsersWithWallets()
    {
        return User::with(['wallet'])->select('id', 'first_name', 'email')->get();
    }




    public function walletFund(){
        $stats = $this->getWalletFundingStatus();
        return view('dashboard.wallet.wallet-funding', compact('stats'));
    }



    private function getWalletFundingStatus()
    {
        $today = now()->startOfDay();

        # Get total internal deposit
        $walletFundingTotal = TransactionLog::where('category', 'deposit')
            ->sum('amount');

        # Get today's internal deposit
        $walletFundingToday = TransactionLog::where('category', 'deposit')
            ->where('created_at', '>=', $today)
            ->sum('amount');

        $externalFundingTotal = TransactionLog::where('category', 'external_bank_deposit')
            ->sum('amount');

        # Get today's external deposit
        $externalFundingToday = TransactionLog::where('category', 'external_bank_deposit')
            ->where('created_at', '>=', $today)
            ->sum('amount');

        # Count pending & failed
        $pendingCount = TransactionLog::where('status', 'pending')->count();
        $failedCount = TransactionLog::where('status', 'failed')->count();
        $users =  $this->loadUsersWithWallets();

        return [
            'wallet_funding_total' => $walletFundingTotal,
            'wallet_funding_today' => $walletFundingToday,
            'external_funding_total' => $externalFundingTotal,
            'external_funding_today' => $externalFundingToday,
            'pending_count' => $pendingCount,
            'failed_count' => $failedCount,
            'users' => $users,
        ];
    }



    public function walletTransactions($user_id)
    {
        $stats = $this->getUserTransactionStats($user_id);

        return view('dashboard.wallet.wallet-transactions', [
            'transactions' => $stats['transactions'],
            'total_transactions' => $stats['total_transactions'],
            'total_amount' => $stats['total_amount'],
            'pending_count' => $stats['pending_count'],
            'failed_count' => $stats['failed_count'],
        ]);
    }


    private function getUserTransactionStats($user_id)
    {
        $transactions = TransactionLog::with('wallet', 'user')
            ->where('user_id', $user_id)
            ->latest()
            ->get();

        return [
            'transactions' => $transactions,
            'total_transactions' => $transactions->count(),
            'total_amount' => $transactions->sum('amount'),
            'pending_count' => $transactions->where('status', 'pending')->count(),
            'failed_count' => $transactions->where('status', 'failed')->count(),
        ];
    }



    private function getWalletFunding($user_id)
    {
        $transactions = TransactionLog::with('wallet', 'user')
            ->where('user_id', $user_id)
            ->latest()
            ->get();

        $today = now()->startOfDay();

        # Wallet funding (internal deposits)
        $walletFunding = $transactions->where('category', 'deposit');
        $todayWalletFunding = $walletFunding->where('created_at', '>=', $today);

        # External deposits (e.g. direct bank transfers)
        $externalFunding = $transactions->where('category', 'external_deposit');
        $todayExternalFunding = $externalFunding->where('created_at', '>=', $today);

        return [
            'transactions' => $transactions,
            'wallet_funding_total' => $walletFunding->sum('amount'),
            'wallet_funding_today' => $todayWalletFunding->sum('amount'),

            # External funding stats
            'external_funding_total' => $externalFunding->sum('amount'),
            'external_funding_today' => $todayExternalFunding->sum('amount'),
        ];
    }


    public function walletTransactionFraudChecks()
    {
        $fraudToday     = true;
        $date_scope     = $fraudToday ? 'Today (' . now()->format('jS M Y') . ')' : 'All Time';
        $total_checks   = FraudCheck::totalChecks($fraudToday);
        $action_stats   = FraudCheck::actionStats($fraudToday);
        $top_txn_types  = FraudCheck::topFlaggedTransactionTypes($fraudToday);

        $fraudChecks = FraudCheck::latestChecks(); // now using model method

        return view('dashboard.wallet.wallet-transactions-fraud-checks', compact(
            'fraudToday',
            'date_scope',
            'total_checks',
            'action_stats',
            'top_txn_types',
            'fraudChecks'
        ));
    }



    public function walletTransactionFraudChecksHistory(){
        return view('dashboard.wallet.wallet-fraud-details');
    }


    public function broadcastMessage(){

        $totalUsers = User::count();
        return view('dashboard.notification.broadcast-message', compact('totalUsers'));
    }





    public function BroadcasterMessage(){
        $totalUsers = User::count();
        return view('dashboard.notification.all-broadcast-messages', compact('totalUsers'));
    }




    public function TransactionReportAnalysis(){

        $transactions = TransactionLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $stats = [
            'totalTransactions' => TransactionLog::count(),
            'totalAmount' => TransactionLog::where('status', 'completed')->sum('amount'),
            'pendingCount' => TransactionLog::where('status', 'pending')->count(),
            'pendingAmount' => TransactionLog::where('status', 'pending')->sum('amount'),
            'failedCount' => TransactionLog::where('status', 'failed')->count(),
            'failedAmount' => TransactionLog::where('status', 'failed')->sum('amount'),
            'paystackCount' => TransactionLog::where('provider', 'paystack')->count(),
            'flutterwaveCount' => TransactionLog::where('provider', 'flutterwave')->count(),
            'vtpassCount' => TransactionLog::where('provider', 'vtpass')->count(),
        ];

        return view('dashboard.transactions.transaction-analysis-reports', compact('transactions') + $stats);
    }
    public function TransactionHistory(){
        return view('dashboard.transactions.bank-transfer');
    }

    public function failedTransactions(){

        $stats = $this->getAllFailedTransactions();
        return view('dashboard.transactions.failed-transaction', $stats);
    }

    public function successfulTransactions(){
        $stats = $this->getAllSuccessfulTransactions();
        return view('dashboard.transactions.successful-transactions', $stats);
    }
    public function review(){
        return view('dashboard.sms.review');
    }
    public function text2pay(){
        return view('dashboard.sms.text2pay');
    }

    public function contacts(){
        return view('dashboard.contact.index');
    }

    public function reports(){
        return view('dashboard.report.index');
    }


    public function email(){
        return view('dashboard.email.index');
    }

    public function settings(){
        return view('dashboard.settings.index');
    }

    public function notification()
    {
        $admin = auth('admin')->user();

        $admin->unreadNotifications->markAsRead();

        $notifications = $admin->notifications;

        return view('dashboard.notification.index', compact('notifications'));
    }


    public function setDollarRate()
    {
        $currentDollarRate = Settings::get('dollar_conversion_rate', 0);

        return view('dashboard.transactions.dollar_rate', compact('currentDollarRate'));
    }

    public function setProvider()
    {

        return view('dashboard.settings.provider');
    }

    public function toggleRestrict(User $user)
        {
            $user->is_account_restricted = !$user->is_account_restricted;
            $user->restriction_date = now();
            $user->save();

            return back()->with('success', 'User restriction status updated successfully.');
        }

        public function toggleBan(User $user)
        {
            $user->is_ban = !$user->is_ban;
            $user->restriction_date = now();
            $user->save();

            return back()->with('success', 'User ban status updated successfully.');
        }


    public function saveAdminUser(createUserAdmin $request) {
        $validated = $request->validated();

        try {
            $admin = Admin::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'] ?? $validated['first_name'],
                'email' => $validated['email'],
                'password' => Hash::make(123),
                'role' => $validated['role'],
                'is_active' => true,
            ]);

            return redirect()->back()->with('success', 'User created successfully!');

        }catch (\Exception $e) {
            \Log::error('Failed to create admin user: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }


    public function updatePermissions(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:admins,id',
            'role' => 'required|string',
            'permissions' => 'array',
        ]);

        $admin = Admin::findOrFail($request->user_id);
        $admin->update([
            'role' => $request->role,
            'permissions' => json_encode($request->permissions ?? []),
        ]);

        return redirect()->back()->with('success', 'Permissions updated successfully!');
    }

    public function destroy($id)
    {
        $admin = Admin::findOrFail($id);

        #  Prevent deleting the last super admin
        if ($admin->role === 'super_admin' && Admin::where('role', 'super_admin')->count() <= 1) {
            return redirect()->back()->with('error', 'Cannot delete the last super admin!');
        }

        $admin->delete();

        return redirect()->back()->with('success', 'User deleted successfully!');
    }


    public function logout(Request $request)
    {
        Auth::guard('admin')->logout(); #  Logout the admin guard

        $request->session()->invalidate();     #  Invalidate the session
        $request->session()->regenerateToken(); #  Regenerate CSRF token

        return redirect()->route('admin.login')->with('success', 'Logged out successfully.');
    }


    private function buildActivityStats(array $metrics): array
    {
        return [
            [
                'label' => 'Total Activities',
                'value' => $metrics['totalActivities'],
                'change' => '+12.3% from last week', #  Placeholder
                'icon' => 'uil-chart-line',
                'color' => 'primary',
                'trend' => 'up',
            ],
            [
                'label' => 'Active Users (24h)',
                'value' => $metrics['activeUsers24h'],
                'change' => '+8.7% increase',
                'icon' => 'uil-users-alt',
                'color' => 'success',
                'trend' => 'up',
            ],
            [
                'label' => 'Unique IPs (7d)',
                'value' => $metrics['uniqueIps'],
                'change' => '-2.1% decrease',
                'icon' => 'uil-globe',
                'color' => 'warning',
                'trend' => 'down',
            ],
            [
                'label' => 'Flagged Activities',
                'value' => $metrics['flaggedActivities'],
                'change' => 'Needs attention',
                'icon' => 'uil-shield-exclamation',
                'color' => 'danger',
                'trend' => 'alert',
            ],
            [
                'label' => 'Top Page Visited',
                'value' => $metrics['topPage'] ?? 'N/A',
                'change' => 'Most viewed',
                'icon' => 'uil-file-alt',
                'color' => 'info',
                'trend' => 'neutral',
            ],
            [
                'label' => 'Top Activity',
                'value' => $metrics['topActivity'] ?? 'N/A',
                'change' => 'Most common action',
                'icon' => 'uil-activity',
                'color' => 'dark',
                'trend' => 'neutral',
            ],
            [
                'label' => 'Hourly Stats Recorded',
                'value' => $metrics['hourlyStats']->count(),
                'change' => 'Hourly breakdown',
                'icon' => 'uil-clock',
                'color' => 'secondary',
                'trend' => 'neutral',
            ],
            [
                'label' => 'Most Active User',
                'value' => $metrics['mostActiveUserName'], #  Fixed: removed ->count()
                'change' => 'Top contributor',
                'icon' => 'uil-user-circle',
                'color' => 'secondary',
                'trend' => 'neutral',
            ],
        ];
    }



    public function savePreferredProvider(PreferredProviderRequest $request)
    {
        try {
            $validated = $request->validated();
            $newProvider = $validated['preferred_provider'];
            $currentProvider = Settings::get('preferred_provider');
//            if ($currentProvider === $newProvider) {
//                return redirect()->back()->with('info', 'Provider is already set to ' . $newProvider);
//            }
            Settings::set('preferred_provider', $validated['preferred_provider']);
            $result = (new VirtualAccountManager())->proceedToAccountCreation($newProvider);
            $message = "Default provider updated successfully. " . $result['message'];
            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'An error occurred while updating the provider: ' . $e->getMessage());
        }
    }



    public function tierSettings(){
        $tiers = Tier::get()->map(function($tier) {
            // Clean and convert string values to float
            $tier->daily_limit = (float) preg_replace('/[₦,\s]/', '', $tier->daily_limit);
            $tier->wallet_balance = (float) preg_replace('/[₦,\s]/', '', $tier->wallet_balance);
            return $tier;
        });

        $totalTiers = $tiers->count();
        $highestDailyLimit = $tiers->max('daily_limit') ?: 0;
        $maxWalletBalance = $tiers->max('wallet_balance') ?: 0;

        $stats = [
            'total_tiers' => $totalTiers,
            'highest_daily_limit' => $highestDailyLimit,
            'max_wallet_balance' => $maxWalletBalance
        ];

        return view('dashboard.transactions.tier-settings', compact('tiers', 'stats'));
    }



    public function updateTier(Request $request): \Illuminate\Http\RedirectResponse
    {
        try {
            // Find the tier by ID from the request
            $tier = Tier::findOrFail($request->id);

            // Selectively update only these specific columns (exclude 'id')
            $updateData = $request->only([
                'status',
                'daily_limit',
                'wallet_balance'
            ]);

            $tier->update($updateData);
            return redirect()->back()->with('success', 'Tier updated successfully!');

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Failed to update tier', [
                'tier_id' => $tier->id,
                'data' => $updateData ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Unable to process request. Please try again.');
        }
    }


    public function saveDollarRate(GlobalRequest $request)
    {
        try {
            $validated = $request->validated();
            Settings::set('dollar_conversion_rate', $validated['dollar_rate']);

            return back()->with('success', 'Dollar conversion rate updated successfully.');
        } catch (\Throwable $e) {
            \Log::error('Failed to save dollar rate: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong. Try again.');
        }
    }



}
