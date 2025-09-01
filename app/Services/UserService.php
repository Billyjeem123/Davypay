<?php

namespace App\Services;

use App\Events\PushNotificationEvent;
use App\Helpers\Utility;
use App\Http\Resources\UserResource;
use App\Jobs\SendPushNotificationJob;
use App\Mail\SendOtpMail;
use App\Models\User;
use App\Models\UserDevice;
use App\Notifications\ForgetPasswordNotification;
use App\Notifications\ForgetPinNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserService
{


    public function processOnboarding001(array $validatedData){

        $user = User::create([
            'first_name' => $validatedData['first_name'],
            'last_name'  => $validatedData['last_name'],
            'email'      => $validatedData['email'],
            'password'   => Hash::make($validatedData['password']),
            'phone'      => $validatedData['phone_number'] ?? null,
            'role'       => 'user',
            'username'   => $validatedData['username'] ?? null,
            'pin'        => Hash::make($validatedData['transaction_pin']),
            'device_token' => $validatedData['device_token'] ?? null,
            'device_type' => $validatedData['device_type'] ?? null,
            'referral_code' => 0,
        ]);



        Log::info('Onboarding started for: ' . $validatedData['email']);


        dispatch(new SendPushNotificationJob(
            $user,
            "welcome Bank Account",
            "yii"
        ));

        dispatch(new SendPushNotificationJob(
            $user,
            'Welcome Back!',
            'Your wallet has been credited broo.'
        ));

//    event(new PushNotificationEvent($user, 'Welcome Back!', 'Your wallet has been credited bee.'));

    }


    public function processOnboarding(array $validatedData)
    {
        return DB::transaction(function () use ($validatedData) {

            # First validate Paystack customer creation (without saving user data yet)
            $paymentResult = $this->validatePaymentCustomerCreation($validatedData);
            if (!$paymentResult['success']) {
                throw new \Exception('Payment customer creation failed: ' . $paymentResult['message']);
            }
            $user = $this->createUser($validatedData);

            $this->assignUserRole($user);
            $this->createUserWallet($user);

            $this->registerUserDevice($user, $validatedData);
            $this->processReferralIfExists($validatedData, $user);

            $this->savePaymentData($paymentResult['data'], $user);
            return $this->generateOnboardingResponse($user);
        });
    }

    private function validatePaymentCustomerCreation(array $validatedData): array
    {
        $provider = Utility::getSetting('preferred_provider');

        if (!$provider) {
            throw new \Exception('No payment provider configured.');
        }

        return match ($provider) {
            'paystack' => (new PaystackService())->testPaystackCustomerCreation($validatedData),
            'nomba' => (new NombaService())->testNombaCustomerCreation($validatedData),
            default =>   (null)
        };
    }

    public function savePaymentData(array $paymentResult, $user): array
    {
        $provider = $paymentResult['provider'] ?? null;

        if (!$provider) {
            throw new \Exception('Payment provider not specified');
        }

        switch ($provider) {
            case 'paystack':
                $result = $this->handlePaystackCustomer($paymentResult, $user->id);
                if (!$result['status']) {
                    throw new \Exception('Failed to create account please try again');
                }
                return ['success' => true, 'provider' => 'paystack'];

            case 'nomba':
                $result = $this->handleNombaCustomer($paymentResult, $user);
                if (!$result['success']) {
                    throw new \Exception('Failed to create account please try again');
                }
                return ['success' => true, 'provider' => 'nomba'];

            default:
                throw new \Exception("Unsupported payment provider: {$provider}");
        }
    }



    private function handlePaystackCustomer(array $paystackData, $userId)
    {
        $paystackService = new PaystackService();
        $customer = $paystackService->saveCustomer($paystackData, $userId);
        $dedicatedAccountResult = $paystackService->createDedicatedAccount($customer->customer_code, $userId);
        $response = $dedicatedAccountResult->getData(true);

        if (!$response['status']) {
            Log::warning('Customer created but dedicated account failed', [
                'customer_code' => $customer->customer_code,
                'error' => $response['message']
            ]);
        }
        return $response;
    }

    /**
     * @throws \Exception
     */
    private function handleNombaCustomer(array $nombaData, $user): array
    {
        $nombaService = new NombaService();
        return $nombaService->createVirtualAccount($nombaData, $user);
    }



    private function registerUserDevice(User $user, array $validatedData): void
    {
        if (!isset($validatedData['device_id'])) {
            return;
        }

        $deviceInfo = [
            'device_fingerprint' => $validatedData['device_fingerprint'] ?? null,
            'device_name' => $validatedData['device_name'] ?? null,
            'device_type' => $validatedData['device_type'] ?? null,
            'device_token' => $validatedData['device_token'] ?? null,
            'platform' => $validatedData['platform'] ?? null,
            'app_version' => $validatedData['app_version'] ?? null,
        ];

        $user->registerDevice($validatedData['device_id'], $deviceInfo);
    }

    private function createUser(array $validatedData): User
    {
        $referralCode = $this->generateUserReferralCode($validatedData);
        $userData = $this->prepareUserData($validatedData, $referralCode);

        return User::create($userData);
    }

    private function generateUserReferralCode(array $validatedData): string
    {
        return User::generateUniqueReferralCode(
            $validatedData['first_name'],
            $validatedData['last_name']
        );
    }

    private function prepareUserData(array $validatedData, string $referralCode): array
    {
        return [
            'first_name' => $validatedData['first_name'],
            'last_name' => $validatedData['last_name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'phone' => $validatedData['phone_number'] ?? null,
            'role' => 'user',
            'username' => $validatedData['username'] ?? null,
            'pin' => Hash::make($validatedData['transaction_pin']),
            'device_token' => $validatedData['device_token'] ?? null,
            'device_type' => $validatedData['device_type'] ?? null,
            'referral_code' => $referralCode,
            'account_level' => 'tier_1'
        ];
    }

    private function assignUserRole(User $user): void
    {
        $user->assignRole('user');
    }

    private function createUserWallet(User $user): void
    {
        $user->wallet()->create([
            'user_id' => $user->id,
            'amount' => 0,
        ]);
    }

    private function processReferralIfExists(array $validatedData, User $user): void
    {
        if (empty($validatedData['referral_code'])) {
            return;
        }

        $referralService = $this->getReferralService();
        $deviceInfo = $this->extractDeviceInfo($validatedData);

        $referralService->processReferral(
            $validatedData['referral_code'],
            $user,
            $deviceInfo
        );
    }

    private function getReferralService(): \App\Services\ReferralService
    {
        return new \App\Services\ReferralService();
    }

    private function extractDeviceInfo(array $validatedData): array
    {
        return [
            'device_type' => $validatedData['device_type'] ?? null,
            'device_token' => $validatedData['device_token'] ?? null,
        ];
    }




    private function handleNombaCustomerCreation( $user): void
    {
        $data = $this->formatNombaCustomerData($user);
        $nomba = new NombaService();
        $nomba->createVirtualAccount($data, $user);

    }





    private function generateOnboardingResponse(User $user): array
    {
        return [
            'user' => new UserResource($user),
            'token' => $this->generateAuthToken($user),
        ];
    }

    private function generateAuthToken(User $user): string
    {
        return $user->createToken('authToken')->plainTextToken;
    }




    public function authenticateUser(array $credentials): \Illuminate\Http\JsonResponse|array
    {
        $loginField = filter_var($credentials['email_or_username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($loginField, $credentials['email_or_username'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return Utility::outputData(false, 'Invalid credentials', [], 401);
        }


        # Check device restrictions if device_id is provided
        if (isset($credentials['device_id'])) {
            $deviceCheckResult = $this->checkDeviceAuthorization($user, $credentials);

            # If device check failed, return the error response
            if ($deviceCheckResult !== true) {
                return $deviceCheckResult;
            }
        }


        return [
            'user' => new UserResource($user),
            'token' => $this->generateAuthToken($user),
        ];
    }


    public function processPinLoginToken(array $data): array
    {
        $user = User::where("email", $data['email'])->first();

        if (!$user || !Hash::check($data['pin'], $user->pin)) {
            return [
                'success' => false,
                'message' => 'Incorrect PIN.',
                'data' => [],
                'status' => 401,
            ];
        }

        $user->device_token = $data['device_token'];
        $token = $this->generateToken($user);
        $user->save();

        return [
            'success' => true,
            'message' => 'User logged in successfully.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $token,
            ],
            'status' => 200,
        ];
    }

    public function generateToken(User $user)
    {
        return $user->createToken('user_token')->plainTextToken;
    }


    /**
     * Check device authorization and register device if valid
     * Returns true if successful, or error response array if failed
     */
    private function checkDeviceAuthorization(User $user, array $credentials): bool|\Illuminate\Http\JsonResponse
    {
        $deviceId = $credentials['device_id'];

        # Check if another user is using this device
//        if (UserDevice::isDeviceInUse($deviceId, $user->id)) {
//            return Utility::outputData(false, 'This device is already registered to another account', [
//                'error_code' => 'DEVICE_ALREADY_REGISTERED',
//                'can_force_login' => true
//            ], 403);
//        }

        # Check if user can login from this device
        if (!$user->canLoginFromDevice($deviceId)) {
//            $user->email_verified_at = null;
            $user->save();
        }

        # Register/update device for user
        $deviceInfo = [
            'device_fingerprint' => $credentials['device_fingerprint'] ?? null,
            'device_name' => $credentials['device_name'] ?? null,
            'device_type' => $credentials['device_type'] ?? null,
            'device_token' => $credentials['device_token'] ?? null,
            'platform' => $credentials['platform'] ?? null,
            'app_version' => $credentials['app_version'] ?? null,
        ];

        # $user->registerDevice($deviceId, $deviceInfo);

        return true; // Success
    }
    public function verifyCredential(array $data): \Illuminate\Http\JsonResponse|array
    {
        $fields = array_filter([
            'email' => $data['email'] ?? null,
            'username' => $data['username'] ?? null,
            'phone' => $data['phone_number'] ?? null,
        ]);

        if (count($fields) !== 1) {
            return Utility::outputData(false, 'Provide exactly one of: email, username, or phone_number.', [], 400);
        }

        $key = array_key_first($fields);
        $value = $fields[$key];
        $exists = User::where($key, $value)->exists();

        return [
            'field' => $key,
            'value' => $value,
            'it_exists' => $exists
        ];
    }

    public function verifyEmailOtp(array $data): \Illuminate\Http\JsonResponse|array
    {
        $email = $data['email'];
        $otp = $data['otp'];

        $storedOtp = Cache::get('verify_email_' . $email);

        if (!$storedOtp) {
            return Utility::outputData(false, 'OTP has expired or is invalid.', [], 400);
        }

        if ($storedOtp != $otp) {
            return Utility::outputData(false, 'Invalid OTP.', [], 400);
        }

        Cache::forget('verify_email_' . $email);

        $user = User::where('email', $email)->first();
        $user->email_verified_at = now();
        $user->save();

        return [
            'email' => $email
        ];
    }

    public function resendEmailOtp(array $data): bool|\Illuminate\Http\JsonResponse
    {
        $email = $data['email'];

        $user = User::where('email', $email)->first();
        if (!$user) {
            return Utility::outputData(false, 'Email not found. Please check and try again.', [], 404);
        }

        $otp = rand(100000, 999999);
        Cache::put('verify_email_' . $email, $otp, now()->addMinutes(10));

        try {
            Mail::to($email)->send(new SendOtpMail($otp));
        } catch (\Exception $e) {
            Log::error("Mail sending failed: " . $e->getMessage());
            return Utility::outputData(false, 'Failed to send OTP. Try again later.', [], 500);
        }

        return true;
    }


    public function processPasswordUpdate(array $validatedData): UserResource
    {
        $user = Auth::user();
        $user->password = \Hash::make($validatedData['new_password']);
        $user->save();

        return new UserResource($user);
    }

    public function forgetPassword(array $data): array
    {

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return ['success' => true,  'data' => [], 'message' => "If an account exists for {$data['email']} , you will receive password reset instructions", 'status' => 200];
        }
        $token =  Utility::token();
        $hashedPassword = Hash::make($token);
        $user->password = $hashedPassword;
        $user->save();

        $user->notify(new ForgetPasswordNotification($user, $token));

        return ['success' => true, 'message' => 'Password sent to mail',  'data' => [], 'status' => 200];

    }


    public function resetPin(array $data): array
    {

        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return ['success' => true,  'data' => [], 'message' => "If an account exists for {$data['email']} , you will receive password reset instructions", 'status' => 200];
        }
        $token =  Utility::pin();
        $hashedPassword = Hash::make($token);
        $user->pin = $hashedPassword;
        $user->save();

        $user->notify(new ForgetPinNotification($user, $token));

        return ['success' => true, 'message' => 'Pin sent',  'data' => [], 'status' => 200];

    }

    public function processTransactionPinUpdate(array $validatedData)
    {
        $user = Auth::user();

        #  Verify current transaction pin
        if (!\Hash::check($validatedData['current_pin'], $user->pin)) {
            return Utility::outputData(false, 'Current transaction PIN is incorrect', [], 400);
        }

        #  Save new transaction pin securely
        $user->pin = \Hash::make($validatedData['new_pin']);
        $user->save();

        return new UserResource($user);
    }


    public function processSavingToken(array $data): array
    {
        $user = Auth::user();
        $user->update(['device_token' => $data['device_token']]);

        return [
            'success' => true,
            'message' => 'Token save successfully.',
            'data' => [$data['device_token']],
            'status' => 200
        ];
    }

    public function processLogout($user): array
    {
        if ($user && $user->currentAccessToken()) {
            $user->tokens()->delete();

            return [
                'success' => true,
                'message' => 'Logout successful. Token revoked.',
                'data' => [],
                'status' => 200
            ];
        }

        return [
            'success' => false,
            'message' => 'No active session found.',
            'data' => [],
            'status' => 400
        ];
    }





    public function fetchAllUsers(?string $search = null): array
    {
        $users = User::with('virtual_accounts:id,user_id,account_number')
            ->select('id', 'first_name', 'last_name', 'username', 'email', 'image')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                        ->orWhereHas('virtual_accounts', function ($q2) use ($search) {
                            $q2->where('account_number', 'like', "%{$search}%");
                        });
                });
            })
            ->get();

        return $users->map(function ($user) {
            return [
                'user_id' => $user->id,
                'first_name' => $user->first_name,
                'username' => $user->username,
                'email' => $user->email,
                'image' => $user->image,
                'last_name' => $user->last_name,
                'account_number' => optional($user->virtual_accounts->first())->account_number,

            ];
        })->toArray();
    }


}
