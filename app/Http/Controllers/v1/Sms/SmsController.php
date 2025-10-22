<?php

namespace App\Http\Controllers\v1\Sms;

use App\Helpers\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkContactUploadRequest;
use App\Http\Requests\GlobalRequest;
use App\Http\Requests\saveContact;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Settings;
use App\Models\TransactionLog;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
{
    public function uploadBulkContact(BulkContactUploadRequest $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();

        try {
            $file = $request->file('csv_file');

            if (!$file || !$file->isValid()) {
                return response()->json(['success' => false, 'message' => 'Uploaded file is not valid.'], 400);
            }

            if ($file->getClientOriginalExtension() !== 'csv') {
                return response()->json(['success' => false, 'message' => 'Please upload a CSV file.'], 400);
            }

            $path = $file->getPathname();
            if (empty($path) || !file_exists($path)) {
                return response()->json(['success' => false, 'message' => 'Unable to access the uploaded file.'], 400);
            }

            if (!($handle = fopen($path, 'r'))) {
                return response()->json(['success' => false, 'message' => 'Unable to read the uploaded file.'], 400);
            }

            $header = fgetcsv($handle);
            if (!$header) {
                fclose($handle);
                return response()->json(['success' => false, 'message' => 'CSV file is empty or has an invalid format.'], 400);
            }

            $header = array_map('trim', $header);
            $allowedHeaders = ['name', 'phone'];
            $unexpectedHeaders = array_diff($header, $allowedHeaders);

            if (!empty($unexpectedHeaders)) {
                fclose($handle);
                return response()->json([
                    'success' => false,
                    'message' => 'CSV contains unexpected columns: ' . implode(', ', $unexpectedHeaders)
                ], 400);
            }

            $rowCount = 0;
            $insertedCount = 0;
            $errors = [];

            while (($row = fgetcsv($handle)) !== false) {
                $rowCount++;

                if (empty(array_filter($row))) {
                    continue;
                }

                $data = array_combine($header, $row);

                foreach ($allowedHeaders as $field) {
                    if (!array_key_exists($field, $data)) {
                        $data[$field] = null;
                    }
                }

                $rules = [];

                if (isset($data['name'])) {
                    $rules['name'] = 'required|string|max:255';
                }
                if (isset($data['phone'])) {
                    $rules['phone'] = 'nullable|string|max:20';
                }

                $rowValidation = Validator::make($data, $rules);

                if ($rowValidation->fails()) {
                    $errors[] = "Row $rowCount: " . implode(', ', $rowValidation->errors()->all());
                    Log::warning("Skipping row $rowCount due to validation error: ", $rowValidation->errors()->toArray());
                    continue;
                }

                try {
                    Contact::create([
                        'name'  => trim($data['name'] ?? ''),
                        'phone' => !empty($data['phone']) ? trim($data['phone']) : null,
                    ]);

                    $insertedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Row $rowCount: Failed to create contact - " . $e->getMessage();
                    Log::error("Failed to create contact for row $rowCount: " . $e->getMessage());
                }
            }

            fclose($handle);

            $message = "$insertedCount of $rowCount contacts imported successfully.";
            $response = [
                'success' => true,
                'message' => $message,
                'inserted' => $insertedCount,
                'processed' => $rowCount,
            ];

            if (!empty($errors)) {
                $response['errors'] = $errors;
                Log::warning('CSV Upload Errors:', $errors);
            }

            return response()->json($response);

        } catch (\Throwable $e) {
            Log::error('CSV Upload Failed: ' . $e->getMessage() . ' | Line: ' . $e->getLine() . ' | File: ' . $e->getFile());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred during CSV upload.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function saveContact(SaveContact $request)
    {
        #  Validate input directly
        $validated =  $request->validated();

        try {
            $contact = Contact::create([
                'name'  => trim($validated['name']),
                'user_id' => Auth::id(),
                'phone' => !empty($validated['phone']) ? trim($validated['phone']) : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Contact saved successfully.',
                'data'    =>  new ContactResource($contact),
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Failed to save contact: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to save contact.',
                'error'   => $e->getMessage()
            ], 500);
        }
    }


    public function getCost(GlobalRequest $request)
    {
        $validated = $request-> validated();
        try {
            $response = Http::withToken(env('SENDTRULY_API_KEY'))
                ->post('https://api.sendtruly.com/api/cost', [
                    'numbers' => $validated['numbers']
                ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                     'message' => 'cost retrieved',
                    'data' => $response->json()['data']

                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve SMS cost.',
                    'error' => $response->json()
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error connecting to SMS API.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function sendSMS(GlobalRequest $request)
    {
        $validated = $request->validated();
        $user = Auth::user();

        // Step 1: Verify transaction PIN
        if (!$this->verifyTransactionPin($user, $validated['transaction_pin'])) {
            return Utility::outputData(false, 'Invalid transaction PIN', null, 403);
        }

        // Step 2: Get balance and wallet
        $balance = Wallet::getBalance();
        if ($balance < $validated['cost']) {
            return Utility::outputData(false, 'Insufficient balance', null, 200);
        }

        $wallet = Wallet::where('user_id', $user->id)->first();
        if (!$wallet) {
            return Utility::outputData(false, 'Wallet not found', null, 404);
        }

        $oldBalance = $wallet->amount;
        $wallet->amount -= $validated['cost'];
        $wallet->save();
        $newBalance = $wallet->amount;

        $payload = [
            'sender_id' => $validated['sender_id'],
            'campaign_name' => $validated['campaign_name'],
            'numbers' => $validated['numbers'],
            'message' => $validated['message']
        ];

        try {
            $response = Http::withToken(env('SENDTRULY_API_KEY'))
                ->post('https://api.sendtruly.com/api/sms/send', $payload);

            if ($response->successful()) {
                $this->logSmsTransaction($user, $wallet, $validated['cost'], $payload, $response->json());
                return Utility::outputData(true, 'SMS sent successfully', $response->json(), 200);
            }

            $this->refundWallet($wallet, $validated['cost']);
            return Utility::outputData(false, 'Failed to send SMS.', $response->json(), $response->status());

        } catch (\Exception $e) {
            $this->refundWallet($wallet, $validated['cost']);
            return Utility::outputData(false, 'Error connecting to SMS API.', $e->getMessage(), 500);
        }
    }


    /**
     * Refund user's wallet
     */
    private function refundWallet(Wallet $wallet, float $amount): void
    {
        $wallet->amount += $amount;
        $wallet->save();
    }
    /**
     * Log a wallet transaction
     */
    private function logSmsTransaction(User $user, Wallet $wallet, float $amount, array $payload, array $response): void
    {
        TransactionLog::create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'category' => 'sms',
            'amount' => $amount,
            'transaction_reference' => Utility::txRef("sms", "system"),
            'service_type' => 'sms',
            'amount_before' => $wallet->amount + $amount,
            'amount_after' => $wallet->amount,
            'status' => 'successful',
            'provider' => 'sendtruly',
            'channel' => 'internal',
            'image' => "https://sendtruly.com/favicon.png",
            'currency' => 'NGN',
            'description' => "Sent SMS campaign",
            'provider_response' => json_encode($response),
            'payload' => json_encode($payload),
        ]);
    }



    /**
     * Verify user's transaction PIN
     */
    private function verifyTransactionPin($user, string $pin): bool
    {
        #  Implement your PIN verification logic here
        #  This could be hashed PIN comparison
        return password_verify($pin, $user->pin);
    }


}
