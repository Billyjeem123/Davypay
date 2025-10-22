<?php

use App\Http\Controllers\v1\Admin\NetworkProviderController;
use App\Http\Controllers\v1\Auth\UserController;
use App\Http\Controllers\v1\Banner\BannerController;
use App\Http\Controllers\v1\Beneficiary\BeneficiaryController;
use App\Http\Controllers\v1\Bill\BillController;
use App\Http\Controllers\v1\Esim\EsimController;
use App\Http\Controllers\v1\Flight\WakanowController;
use App\Http\Controllers\v1\Kyc\KycController;
use App\Http\Controllers\v1\Notification\NotificationController;
use App\Http\Controllers\v1\Payment\NombaController;
use App\Http\Controllers\v1\Payment\NombaTransferController;
use App\Http\Controllers\v1\Payment\NombaWalletTransferController;
use App\Http\Controllers\v1\Payment\PaymentChargesController;
use App\Http\Controllers\v1\Payment\PaystackController;
use App\Http\Controllers\v1\Payment\PaystackTransferController;
use App\Http\Controllers\v1\Payment\SecureInAppTransferController;
use App\Http\Controllers\v1\Referrral\ReferralController;
use App\Http\Controllers\v1\Sms\SmsController;
use App\Http\Controllers\v1\Tier\TierController;
use App\Http\Controllers\v1\Transaction\TransactionController;
use App\Http\Controllers\v1\Upload\UploadController;
use App\Http\Controllers\v1\VirtualCard\CardController;
use App\Http\Controllers\v1\VirtualCard\EversendCardController;
use App\Http\Controllers\v1\VirtualCard\GiftCardController;
use App\Http\Controllers\v1\VirtualCard\StrollWalletController;
use App\Http\Controllers\v1\VirtualCard\StroUsdtController;
use App\Http\Controllers\v1\Webhook\CardWebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = Auth::user()->load(['wallet', 'virtual_accounts', 'virtual_cards', 'usdtWallet']);
    return new \App\Http\Resources\UserResource($user);
});

# Authentication
Route::prefix('auth')->group(function () {
    Route::post('register', [UserController::class, 'Register']);
    Route::post('login', [UserController::class, 'Login'])->name('login');
    Route::post('/resend-email-otp', [UserController::class, 'resendEmailOTP']);
    Route::post('/credential-exists', [UserController::class, 'checkCredential']);
    Route::post('/verify-email', [UserController::class, 'confirmEmailOtp']);
    Route::post('/change-password', [UserController::class, 'updatePassword'])->middleware('auth:sanctum');
    Route::post('/forget-password', [UserController::class, 'forgetPassword']);
    Route::post('/save-token', [UserController::class, 'saveToken'])->middleware('auth:sanctum');
    Route::post('/change-pin', [UserController::class, 'updateTransactionPin'])->middleware('auth:sanctum');
    Route::post('/logout', [UserController::class, 'Logout'])->middleware('auth:sanctum');
    Route::post('/login-pin', [UserController::class, 'LoginWithPin']);
    Route::post('/reset-pin', [UserController::class, 'resetPin']);
});


# Bill Payment
Route::prefix('bill')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/get-airtime-list', [BillController::class, 'get_airtime_list']);
    Route::get('/get-data-list', [BillController::class, 'get_data_list']);
    Route::get('/get-data-sub-option', [BillController::class, 'get_data_sub_option']);
    Route::post('/buy-airtime', [BillController::class, 'buyAirtime'])->middleware('idempotency:true');
    Route::post('/buy-data', [BillController::class, 'buyData'])->middleware('idempotency:true');
    Route::get('/get-cable-lists-option', [BillController::class, 'get_cable_lists_option']);
    Route::get('/get-cable-lists', [BillController::class, 'get_cable_lists']);
    Route::get('/verify-cable', [BillController::class, 'verify_cable']);
    Route::post('/buy-cable', [BillController::class, 'buy_cable'])->middleware('idempotency:true');

    # Electricity
    Route::get('/get-electricity-lists-option', [BillController::class, 'get_electricity_lists_option']);
    Route::get('/get-electricity-lists', [BillController::class, 'get_electricity_lists']);
    Route::get('/verify-electricity', [BillController::class, 'verify_electricity']);
    Route::post('/buy-electricity', [BillController::class, 'buy_electricity'])->middleware('idempotency:true');

    Route::get('/get-jamb-lists-option', [BillController::class, 'get_jamb_lists_option']);
    Route::get('/get-jamb-lists', [BillController::class, 'get_jamb_list']);
    Route::post('/buy-jamb', [BillController::class, 'buy_jamb'])->middleware('idempotency:true');
    Route::get('/verify-jamb', [BillController::class, 'verify_jamb']);

    Route::get('/get-waec-lists-option', [BillController::class, 'get_waec_lists_option']);
    Route::get('/get-waec-lists', [BillController::class, 'get_waec_list']);
    Route::post('/buy-waec', [BillController::class, 'buy_waec_direct']);


    Route::get('/get-broadband-lists-option', [BillController::class, 'get_broadband_lists_option']);
    Route::get('/get-broadband-lists', [BillController::class, 'get_broadband_list']);
    Route::post('/buy-broadband-spectranent', [BillController::class, 'buy_broadband_spectranent'])->middleware('idempotency:true');
    Route::post('/buy-broadband-smile', [BillController::class, 'buy_broadband_smile'])->middleware('idempotency:true');
    Route::post('/verify-broadband-smile', [BillController::class, 'verify_broadband_smile']);


    Route::get('/get-international-countries', [BillController::class, 'get_international_countries']);
    Route::get('/get-international-airtime-product-types', [BillController::class, 'get_international_airtime_product_types']);
    Route::get('/get-international-airtime-operators', [BillController::class, 'get_international_airtime_operators']);
    Route::get('/get-international-airtime-variation', [BillController::class, 'get_international_airtime_variation']);
    Route::post('/buy-international-airtime', [BillController::class, 'buy_international_airtime'])->middleware('idempotency:true');


    Route::get('/get-giftcard-lists', [BillController::class, 'get_giftcard_list']);
    Route::post('/buy-giftcard', [BillController::class, 'buy_giftcard'])->middleware('idempotency:true');
});


# Transactions
Route::prefix('transaction')->middleware('auth:sanctum')->group(function () {
    Route::get('/get/user/history/{id?}', [TransactionController::class, 'myTransactionHistory']);
    Route::get('/recent-transfers', [TransactionController::class, 'recentTransfers']);
    Route::post('/send/transaction/history/pdf', [TransactionController::class, 'sendTransactionHistoryPdf']);


});


# Beneficiary
Route::prefix('beneficiary')->middleware(['auth:sanctum'])->group(function () {
    Route::post('/create', [BeneficiaryController::class, 'createBeneficiary']);
    Route::post('/delete', [BeneficiaryController::class, 'deleteBeneficiary']);
    Route::get('/user/all/{id?}', [BeneficiaryController::class, 'getBeneficiary']);
});

Route::prefix('kyc')->middleware('auth:sanctum')->group(function () {
    Route::post('/verify-bvn', [KycController::class, 'verifyBvn']);
    Route::post('/verify-nin', [KycController::class, 'verifyNin']);
    Route::post('/verify-dl', [KycController::class, 'verifyDriverLicense']);
    Route::get('/tiers-list', [TierController::class, 'getAllTiers']);
    Route::post('/subscribe', [KycController::class, 'subscribeToDojah']);
});


Route::prefix('webhook')->group(function () {
    Route::post('/nomba', [\App\Http\Controllers\v1\Webhook\NombaWebhookController::class, 'nombaWebhook']);
    Route::post('/verify-vtu-bills', [\App\Http\Controllers\v1\Webhook\VTpassWebhookController::class, 'processVtPassWebHook']);
    Route::post('/paystack', [\App\Http\Controllers\v1\Webhook\PaystackWebhookController::class, 'paystackWebhook']);
    Route::post('/redbiller', [\App\Http\Controllers\v1\Webhook\RedbillerWebhookController::class, 'redBiller3dWebhook']);
    Route::post('/kyc', [\App\Http\Controllers\v1\Webhook\DojahWebhookController::class, 'dojahWebhook']);
    Route::post('/virtual-card', [\App\Http\Controllers\v1\Webhook\VirtualCardWebhookController::class, 'handle'])->middleware('verify.strowallet');


});


Route::prefix('payment')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Paystack Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/paystack-initiate-payment', [PaystackController::class, 'initializeTransaction'])->middleware('auth:sanctum');
    Route::get('/paystack-callback', [PayStackController::class, 'verifyTransaction'])->name('paystack.callback');
    Route::get('/preferred-payment', [\App\Http\Controllers\v1\Payment\PreferredPaymentController::class, 'index']);

    /*
    |--------------------------------------------------------------------------
    | Nomba Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/nomba-initialize-payment', [NombaController::class, 'initializePayment'])->middleware('auth:sanctum');
    Route::get('/nomba-callback', [NombaController::class, 'nombaCallback'])->name('nomba.callback');
    Route::post('/nomba-to-nomba-transfer', [NombaWalletTransferController::class, 'initializeWalletTransfer'])->middleware('auth:sanctum');


    /*
    |--------------------------------------------------------------------------
    | In-App Transfer
    |--------------------------------------------------------------------------
    */
    Route::post('/in-app-transfer', [SecureInAppTransferController::class, 'InAppTransferNow'])->middleware(['auth:sanctum', 'idempotency:true']);
    Route::post('/calculate-charges', [PaymentChargesController::class, 'calculateFee']);
    Route::post('/get-service-charge', [PaymentChargesController::class, 'getServiceCharge']);
    Route::post('pay/epin/permit', [PaymentChargesController::class, 'payPermit']);


    /*
    |--------------------------------------------------------------------------
    | Transfers (Bank & Nomba)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum'])->prefix('transfer')->group(function () {

        # Paystack Transfer
        Route::post('/bank', [PaystackTransferController::class, 'transferToBank'])->middleware('idempotency:true');
        Route::get('/banks', [PaystackTransferController::class, 'getBanks']);
        Route::post('/resolve-account', [PaystackTransferController::class, 'resolveAccount']);
        Route::get('/status/{reference}', [PaystackTransferController::class, 'verifyTransferStatus']);

        # You can add Nomba-specific transfer routes below if any
        Route::prefix('nomba')->group(function () {
            Route::get('/list-of-bank', [NombaController::class, 'getBanks']);
            Route::post('/resolve-account', [NombaController::class, 'resolveAccount']);
            Route::post('/transfer', [NombaTransferController::class, 'transferToBank'])->middleware('idempotency:true');

        });
    });

});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/referral/link', [ReferralController::class, 'getReferralLink']);
    Route::get('/referral/history', [ReferralController::class, 'getReferralHistory']);
    Route::get('/referral/stats', [ReferralController::class, 'getReferralStats']);
});


Route::middleware(['auth:sanctum', 'tier:tier_3'])->prefix('eversend')->group(function () {
    Route::post('/card/user', [EversendCardController::class, 'createCardUser']);
    Route::post('/card/create', [EversendCardController::class, 'createVirtualCard']);
    Route::get('/card/details/{card_id}', [EversendCardController::class, 'getCardId']);
    Route::get('/card/transaction/{card_id}', [EversendCardController::class, 'getCardTransactions']);
    Route::post('/card/fund-wallet', [EversendCardController::class, 'FundWallet'])->middleware('idempotency:true');
    Route::post('/card/withdrawal', [EversendCardController::class, 'Withdrawal']);
    Route::post('/card/freeze', [EversendCardController::class, 'FreezeACard']);
    Route::post('/card/unfreeze', [EversendCardController::class, 'UnFreezeACard']);
    Route::post('/card/terminate', [EversendCardController::class, 'terminateACard']);
});


Route::middleware(['auth:sanctum'])->prefix('strollwallet')->group(function () {
    Route::post('/create/account', [StrollWalletController::class, 'createAccount']);
    Route::get('/get-customer', [StrollWalletController::class, 'getCustomerData']);
    Route::post('/create/card', [StrollWalletController::class, 'createCard']);
    Route::post('/update/customer', [StrollWalletController::class, 'updateCustomer']);
    Route::get('/card-info/{card_id}', [StrollWalletController::class, 'getCardDetails']);
    Route::post('/fund/account', [StrollWalletController::class, 'FundWallet']);
    Route::post('/card-transactions/{card_id}', [StrollWalletController::class, 'getCardTransactions']);
    Route::post('/withdrawal', [StrollWalletController::class, 'WithdrawFromCard']);
    Route::post('/withdrawal/status', [StrollWalletController::class, 'withdrawalStatus']);
    Route::get('/transaction-settings', [StrollWalletController::class, 'getVirtualSettings']);
    Route::post('/freeze-unfreeze', [StrollWalletController::class, 'freezeUnFreezeCard']);
    Route::post('/card/terminate', [EversendCardController::class, 'terminateACard']);
    Route::post('/create/usdt', [StroUsdtController::class, 'generateUsdtAddress']);
    Route::get('/usdt-history', [StroUsdtController::class, 'getUsdtHistory']);
    Route::post('/send-usdt', [StroUsdtController::class, 'sendUsdt']);
    Route::post('/webhook', [StroUsdtController::class, 'handle']);
    Route::get('/usdt/exchange-rate', [StroUsdtController::class, 'getExchangeRate']);
    Route::post('/usdt/convert', [StroUsdtController::class, 'convert']);
    Route::post('/virtual-card/fund-usdt', [StroUsdtController::class, 'fundFromUsdt']);
    Route::get('/epin/provider', [StroUsdtController::class, 'getProviders']);
    Route::post('/epin/buy', [StroUsdtController::class, 'buy']);
    Route::get('/epin/files', [StroUsdtController::class, 'getMyPdfs']);
    Route::get('/epin/download/{filename}', [StroUsdtController::class, 'downloadByFilename']);

});

Route::middleware(['auth:sanctum', 'tier:tier_3'])->prefix('cards')->group(function () {
    Route::post('/create/naira-card', [CardController::class, 'createNairaCard']);
    Route::post('/change-pin', [CardController::class, 'changePin']);
    Route::get('/get/naira-card', [CardController::class, 'viewPhysicalCard']);
    Route::get('/history', [CardController::class, 'viewCardHistory']);
    Route::post('/enable-2fa', [CardController::class, 'enable2fa']);
    Route::post('/reset-pin', [CardController::class, 'resetPin']);
    Route::post('/create-dispute', [CardController::class, 'createDispute']);
    Route::post('/update-status', [CardController::class, 'updateCardStatus']);
    Route::post('/delivery', [CardController::class, 'updateAddress']);
    Route::get('/fees', [CardController::class, 'getFees']);
});

Route::prefix('betting')->group(function () {
    Route::get('/betsites', [\App\Http\Controllers\v1\Betting\BettingController::class, 'getBetSites'])->middleware('auth:sanctum');
    Route::get('/verify-account', [\App\Http\Controllers\v1\Betting\BettingController::class, 'verifyBettingAccount'])->middleware('auth:sanctum');
    Route::post('/redbiller-fund', [\App\Http\Controllers\v1\Betting\BettingController::class, 'fundBettingAccount'])->middleware(['auth:sanctum', 'throttle:5,1', 'api', 'idempotency:true']);
    Route::get('/verify-transaction/{reference}', [\App\Http\Controllers\v1\Betting\BettingVerificationController::class, 'verifyTransaction'])->name('betting.callback');

});


Route::prefix('airtime-to-cash')->middleware('auth:sanctum')->group(function () {
    Route::get('/provider', [NetworkProviderController::class, 'getActiveProviders']);
    Route::post('/submit', [NetworkProviderController::class, 'submitRequest'])->middleware('idempotency:true');
    Route::get('/user-requests', [NetworkProviderController::class, 'userRequests']);
    Route::post('/network-provider/calculate-amount', [NetworkProviderController::class, 'calculateAmount'])->name('admin.network-provider.calculate');

});


Route::prefix('notification')->middleware('auth:sanctum')->group(function () {
    Route::get('/all', [NotificationController::class, 'getAllNotifications']);
    Route::get('/unread-count', [NotificationController::class, 'getUnreadCount']);
    Route::get('/all-count', [NotificationController::class, 'getAllCount']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::get('/read/{id}', [NotificationController::class, 'markAsRead']);
});


Route::prefix('banner')->middleware('auth:sanctum')->group(function () {
    Route::get('/all', [BannerController::class, 'getActiveBanners']);

});

Route::middleware(['auth:sanctum'])->prefix('wakanow')->group(function () {
    Route::post('flight/search', [WakanowController::class, 'searchFlights']);
    Route::post('flight/select', [WakanowController::class, 'selectFlight']);
    Route::post('flight/book', [WakanowController::class, 'bookFlight']);
    Route::post('flight/ticket', [WakanowController::class, 'ticketFlight']);
    Route::get('flight/airports', [WakanowController::class, 'airports']);
    Route::get('flight/airports-raw', [WakanowController::class, 'airportsRaw']);
    Route::get('wallet', [WakanowController::class, 'walletBalance']);
});


Route::prefix('bulk-sms')->middleware('auth:sanctum')->group(function () {
    Route::post('/contact', [SmsController::class, 'saveContact']);
    Route::post('/bulk-upload', [SmsController::class, 'uploadBulkContact']);
    Route::post('/cost', [SmsController::class, 'getCost']);
    Route::post('/send', [SmsController::class, 'sendSMS']);

});

Route::prefix('giftcards')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [GiftCardController::class, 'index']);
    Route::get('/my-list', [GiftCardController::class, 'myList']);
    Route::post('/', [GiftCardController::class, 'sell']);
});


Route::prefix('esim')->middleware('auth:sanctum')->group(function () {
    Route::get('/data-plan', [EsimController::class, 'getDataPlans']);
    Route::post('/create', [EsimController::class, 'createEsim']);
    Route::post('/activate/{iccid}', [EsimController::class, 'ActivateEsim']);
    Route::get('/profile/{iccid}', [EsimController::class, 'getICCIDProfile']);
    Route::get('/data-usage/{iccid}', [EsimController::class, 'getDataUsage']);
    Route::get('/countries', [EsimController::class, 'getCountries']);
    Route::get('/my-sim', [EsimController::class, 'myEsims']);
    Route::get('/settings', [EsimController::class, 'getEsimSettings']);

});

Route::post('/webhooks/card', [CardWebhookController::class, 'handle'])->name('webhook.strowallet.card');
Route::post('/webhooks/card/authorization', [CardWebhookController::class, 'handleAuthorization']);
Route::post('/webhooks/card/transaction-created', [CardWebhookController::class, 'handleTransactionCreated']);
Route::post('/webhooks/card/transaction-refund', [CardWebhookController::class, 'handleTransactionRefund']);

Route::post('/document/upload', [\App\Http\Controllers\v1\Upload\UploadController::class, 'documentUploads']);
Route::get('/test-auth', [NombaController::class, 'testAuthentication']);
Route::get('/all', [UserController::class, 'allUsers']);
Route::get('/all-announcement', [UserController::class, 'allAnnouncement']);
Route::post('/update-profile-image', [UploadController::class, 'updateProfileImage'])->middleware('auth:sanctum');
Route::get('/test-artisan', [NombaController::class, 'testArtisan']);
