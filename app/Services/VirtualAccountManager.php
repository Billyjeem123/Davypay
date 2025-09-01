<?php

namespace App\Services;

use App\Helpers\AccountManagerLogger;
use App\Models\PaystackCustomer;
use App\Models\User;

class VirtualAccountManager
{

    /**
     * Create virtual accounts for ALL users for the new provider
     *
     * @param string $provider The provider to create accounts for
     * @return array Result of the operation
     */
    public function proceedToAccountCreation(string $provider): array
    {
        try {
            // Get users who DON'T have a virtual account for this specific provider
            $usersNeedingAccount = $this->getUsersWithoutProviderAccount($provider);

            if ($usersNeedingAccount->isEmpty()) {
                return [
                    'success' => true,
                    'message' => "All users already have {$provider} account numbers.",
                    'processed' => 0
                ];
            }
            $successCount = 0;
            $failureCount = 0;

            AccountManagerLogger::log("Starting account creation for {$usersNeedingAccount->count()} users with provider: {$provider}");

            foreach ($usersNeedingAccount as $user) {
                try {
                    $this->createVirtualAccountForUser($user, $provider);
                    $successCount++;

                    AccountManagerLogger::log("Successfully created {$provider} account for user {$user->id}");

                } catch (\Exception $e) {
                    $failureCount++;
                    AccountManagerLogger::error("Failed to create {$provider} account for user {$user->id}: " . $e->getMessage());
                    // Continue with other users
                    continue;
                }
            }

            $totalUsers = $usersNeedingAccount->count();
            $message = "Created {$provider} account numbers for {$successCount} out of {$totalUsers} users.";

            if ($failureCount > 0) {
                $message .= " {$failureCount} failed.";
            }

            return [
                'success' => true,
                'message' => $message,
                'processed' => $successCount,
                'failed' => $failureCount,
                'total' => $totalUsers
            ];

        } catch (\Exception $e) {
            AccountManagerLogger::error('Account creation process failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Account creation failed: ' . $e->getMessage(),
                'processed' => 0
            ];
        }
    }

    /**
     * Get users who DON'T have virtual accounts for the specified provider
     * This ensures we only create accounts for users who don't already have them
     *
     * @param string $provider
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function getUsersWithoutProviderAccount(string $provider): \Illuminate\Database\Eloquent\Collection
    {
        return User::whereDoesntHave('allVirtualAccounts', function ($query) use ($provider) {
            $query->where('provider', $provider);
        })
            ->get();
    }


    /**
     * Create virtual account for a specific user and provider
     *
     * @param User $user
     * @param string $provider
     * @return void
     * @throws \Exception
     */
    private function createVirtualAccountForUser($user, string $provider): void
    {
        switch ($provider) {
            case 'paystack':
                $this->createPaystackAccountNumber($user);
                break;

            case 'nomba':
                $this->createNombaAccountNumber($user);
                break;

            default:
                throw new \Exception("Unsupported provider: {$provider}");
        }
    }

    /**
     * Create Paystack account number for user
     *
     * @param User $user
     * @return void
     * @throws \Exception
     */
    private function createPaystackAccountNumber($user): void
    {
        $paystackService = new PaystackService();
        $paystackCustomer = PaystackCustomer::where('user_id', $user->id)->first();
        if (!$paystackCustomer) {
            $customerData = [
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'phone' => $user->phone,
            ];
            $customerResult = $paystackService->createCustomerValidationOnly($customerData);

            if (!$customerResult['success']) {
                throw new \Exception('Failed to create Paystack customer: ' . $customerResult['message']);
            }
            $paystackCustomer = $paystackService->saveCustomer($customerResult['data'], $user->id);
        }
        $dedicatedAccountResult = $paystackService->createDedicatedAccount($paystackCustomer->customer_code, $user->id);
        $response = $dedicatedAccountResult->getData(true);

        if (!$response['status']) {
            throw new \Exception('Failed to create Paystack account number: ' . ($response['message'] ?? 'Unknown error'));
        }
    }

    /**
     * Create Nomba account number for user
     *
     * @param User $user
     * @return void
     * @throws \Exception
     */
    private function createNombaAccountNumber($user): void
    {
        $nombaService = new NombaService();
         $userData = [
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'id' => $user->id,
        ];

        // Test Nomba customer creation first
        $customerResult = $nombaService->testNombaCustomerCreation($userData);

        if (!$customerResult['success']) {
            throw new \Exception('Failed to create Nomba customer: ' . $customerResult['message']);
        }

        // Handle the Nomba customer creation and virtual account
        $result = $this->handleNombaCustomer($customerResult['data'], $user);

        if (!$result['success']) {
            throw new \Exception('Failed to create Nomba account number: ' . $result['message']);
        }
    }

    /**
     * @throws \Exception
     */
    private function handleNombaCustomer(array $nombaData, $user): array
    {
        $nombaService = new NombaService();
        return $nombaService->createVirtualAccount($nombaData, $user);
    }

}



