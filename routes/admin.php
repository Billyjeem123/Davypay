<?php

use App\Http\Controllers\v1\Admin\AdminController;
use App\Http\Controllers\v1\Admin\AdminWalletTransactionController;
use App\Http\Controllers\v1\Admin\NetworkProviderController;
use App\Http\Controllers\v1\Admin\ProfileController;
use App\Http\Controllers\v1\Admin\PushNotificationController;
use App\Http\Controllers\v1\Admin\TransactionFeeController;
use App\Http\Controllers\v1\Banner\BannerController;
use Illuminate\Broadcasting\BroadcastController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!!
|
*/


Route::view('/admin/login', 'login')->name('admin.login');
Route::post('/admin/login', [\App\Http\Controllers\v1\Admin\AdminAuthController::class, 'loginAdmin'])->name('login.submit');
Route::middleware(['admin.auth'])->prefix('admin')->group(function () {

    /**
     * Dashboard & Home
     */
    Route::get('/', [AdminController::class, 'index'])->name('admin.home');
    Route::get('/logout', [AdminController::class, 'logout'])->name('admin.logout');

    /**
     * User Management
     */
    Route::get('/user-management', [AdminController::class, 'userManagement'])->name('admin.management');
    Route::get('/user-management/{id}', [AdminController::class, 'UserFullInformation'])->name('user.info');
    Route::get('/active-users', [AdminController::class, 'activeUsers'])->name('active.users');
    Route::get('/suspended-users', [AdminController::class, 'suspendedUsers'])->name('suspended.users');
    Route::get('/pending-verification-users', [AdminController::class, 'pendingVerificationUsers'])->name('pending.verification.users');
    Route::get('/user/kyc', [AdminController::class, 'userKyc'])->name('users.kyc');
    Route::get('/user/roles', [AdminController::class, 'userRoles'])->name('users.role');
    Route::get('/user/permission/assign', [AdminController::class, 'userPermission'])->name('users.permission');
    Route::get('/user/kyc/tier-1', [AdminController::class, 'usersInTier1'])->name('users.tier_1');
    Route::get('/user/kyc/tier-2', [AdminController::class, 'usersInTier2'])->name('users.tier_2');
    Route::get('/user/kyc/tier-3', [AdminController::class, 'usersInTier3'])->name('users.tier_3');
    /**
     * Admin Users & Permissions
     */
    Route::post('/admin/users', [AdminController::class, 'saveAdminUser'])->name('admin.users.store');
    Route::post('/admin/users/permissions', [AdminController::class, 'updatePermissions'])->name('admin.users.permissions.update');
    Route::delete('/admin/users/{id}', [AdminController::class, 'destroy'])->name('admin.users.destroy');
    Route::post('/admin/users/{user}/toggle-restrict', [AdminController::class, 'toggleRestrict'])->name('admin.users.toggle-restrict');
    Route::post('/admin/users/{user}/toggle-ban', [AdminController::class, 'toggleBan'])->name('admin.users.toggle-ban');

    /**
     * Activity Logs
     */
    Route::get('/activity/logs', [AdminController::class, 'ActivityLogs'])->name('users.activity');
    Route::get('/user/activity/info/{id}', [AdminController::class, 'ActivityLogsDetails'])->name('users.log-details');

    /**
     * Transaction Management
     */

    Route::get('/transaction-fee', [AdminController::class, 'getTransactionFees'])->name('transaction.fee');
    Route::get('/configure-payment-gateway', [TransactionFeeController::class, 'PaymentConfiguration'])->name('configure-payment');
    Route::post('/transaction-fee/update', [TransactionFeeController::class, 'updateTransactionFee'])->name('transaction-fee.update');
    Route::delete('/transaction-fee/{id}', [TransactionFeeController::class, 'deleteFee'])->name('transaction-fee.destroy');
    Route::get('/tier-settings', [AdminController::class, 'tierSettings'])->name('tier_settings');
    Route::get('/set-dollar-rate', [AdminController::class, 'setDollarRate'])->name('dollar_rate');
    Route::get('/set-preferred-provider', [AdminController::class, 'setProvider'])->name('set_preferred_provider');
    Route::post('/save-dollar-rate', [AdminController::class, 'saveDollarRate'])->name('save_dollar_rate');
    Route::post('/tiers', [AdminController::class, 'updateTier'])->name('tiers.update');
    Route::post('/save-transfer-fee', [TransactionFeeController::class, 'saveTransferFee'])->name('save.transfer.fee');
    Route::post('/save-deposit-fee', [TransactionFeeController::class, 'saveTransferFeeDeposit'])->name('save.deposit.fee');
    Route::get('/all-transaction', [AdminController::class, 'allTransactions'])->name('all.transactions');
    Route::get('/pending-transaction', [AdminController::class, 'pendingTransactions'])->name('pending.transactions');
    Route::get('/failed-transaction', [AdminController::class, 'failedTransactions'])->name('failed.transactions');
    Route::get('/successful-transaction', [AdminController::class, 'successfulTransactions'])->name('successful.transactions');
    Route::get('/report-transaction', [AdminController::class, 'reportTransactions'])->name('transactions.report');
    Route::get('/transactions-bank-transfers', [AdminController::class, 'TransactionHistory'])->name('bank.transfers');
    Route::post('/user/save-provider', [AdminController::class, 'savePreferredProvider'])->name('user.save-provider');
    /**
     * Wallet Management
     */
    Route::get('/wallet-overview', [AdminController::class, 'walletOverview'])->name('wallet-report');
    Route::get('/wallet-funding', [AdminController::class, 'walletFund'])->name('wallet-funding');
    Route::post('/admin/fund-wallet', [AdminWalletTransactionController::class, 'fund'])->name('admin.wallet.fund');
    Route::get('/wallet-transactions/{id}', [AdminController::class, 'walletTransactions'])->name('wallet-transactions');





    /**
     * Fraud & Analysis
     */
    Route::get('/user-transaction-fraud-checks', [AdminController::class, 'walletTransactionFraudChecks'])->name('wallet-transactions.fraud.checks');
    Route::get('/fraud-checks-history', [AdminController::class, 'walletTransactionFraudChecksHistory'])->name('fraud.transaction.history');
    Route::get('/transaction-report-analysis', [AdminController::class, 'TransactionReportAnalysis'])->name('transaction-report-analysis');

    /**
     * Broadcast Notifications
     */
    Route::get('/broadcast-message', [AdminController::class, 'broadcastMessage'])->name('broadcast-message');
    Route::get('/all-broadcast-message', [AdminController::class, 'BroadcasterMessage'])->name('all-broadcast-message');
    Route::get('/dashboard/broadcast-message', [BroadcastController::class, 'broadcastMessage'])->name('dashboard.broadcast.message');
    Route::post('/dashboard/broadcast/send', [PushNotificationController::class, 'sendBroadcast'])->name('dashboard.broadcast.send');


    /**
     * AJAX API Endpoints
     */
    Route::get('/api/users/dropdown', [PushNotificationController::class, 'getUsersForDropdown'])->name('api.users.dropdown');
    Route::get('/api/users', [PushNotificationController::class, 'getUsers'])->name('api.users');

    /**
     * Admin Profile
     */
    Route::get('/profile', [ProfileController::class, 'index'])->name('user.settings');
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('admin.profile.update');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword'])->name('admin.profile.change-password');

    /**
     * Admin Notifications
     */
    Route::get('/notification', [AdminController::class, 'notification'])->name('user.notification');


    Route::get('/airtime-to-cash', [NetworkProviderController::class, 'index'])->name('airtime_to_cash');
    Route::get('/airtime-to-cash-records', [NetworkProviderController::class, 'allRecords'])->name('airtime_to_cash_records');
    Route::post('/network-provider', [NetworkProviderController::class, 'store'])->name('admin.network-provider.store');
    Route::put('/network-provider/{id}', [NetworkProviderController::class, 'update'])->name('admin.network-provider.update');
    Route::patch('/network-provider/{id}/toggle', [NetworkProviderController::class, 'toggle'])->name('admin.network-provider.toggle');
    Route::delete('/network-provider/{id}', [NetworkProviderController::class, 'destroy'])->name('admin.network-provider.destroy');

    // Additional utility routes
    Route::get('/network-providers/active', [NetworkProviderController::class, 'getActiveProviders'])->name('admin.network-provider.active');
    Route::post('/network-provider/calculate-amount', [NetworkProviderController::class, 'calculateAmount'])->name('admin.network-provider.calculate');
    Route::patch('/network-provider/approve-transfer/{id}', [NetworkProviderController::class, 'approveTransfer'])->name('network-provider.approve-transfer');
    Route::patch('/network-provider/reject-transfer/{id}', [NetworkProviderController::class, 'rejectTransfer'])->name('network-provider.reject-transfer');
    Route::delete('/network-provider/delete-transfer/{id}', [NetworkProviderController::class, 'deleteTransfer'])->name('network-provider.delete-transfer');
    #banner page

    Route::get('/banners', [BannerController::class, 'index'])->name('banners.index');
    Route::post('/banners/upload', [BannerController::class, 'uploadBanner'])->name('banners.upload');
    Route::patch('/banners/{id}/activate', [BannerController::class, 'activate'])->name('banners.activate');
    Route::patch('/banners/{id}/deactivate', [BannerController::class, 'deactivate'])->name('banners.deactivate');
    Route::delete('/banners/{id}', [BannerController::class, 'delete'])->name('banners.delete');


});


