<?php

namespace App\Services;

use App\Helpers\UsdtLogger;
use App\Exceptions\StroUsdtException;
use App\Helpers\Utility;
use App\Models\TransactionFee;
use App\Models\TransactionLog;
use App\Models\UsdtTransaction;
use App\Models\UsdtWallet;
use App\Models\VirtualCard;
use App\Models\Wallet;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class StroUsdtService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $publicKey;
    protected string $mode;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.strowallet.base_url', 'https://strowallet.com/api'), '/');
        $this->apiKey = config('services.strowallet.secret');
        $this->publicKey = config('services.strowallet.public_key');
        $this->mode =  'sandbox';
        // $mode = $mode ?? (config('app.env') === 'production' ? 'live' : 'sandbox');
    }

    public function request(string $method, string $endpoint, array $data = [], bool $useAuth = true)
    {
        $headers = [
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ];

        if ($useAuth) {
            $headers['Authorization'] = "Bearer {$this->apiKey}";
        }

        $response = Http::withHeaders($headers)
            ->timeout(30)
            ->retry(3, 200)
            ->{$method}($this->baseUrl . $endpoint, $data);

        if (!$response->successful()) {
            UsdtLogger::log('Strowallet API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $data,
            ]);

            throw new StroUsdtException(
                'Strowallet API Error: ' . $response->body(),
                $response->status(),
                ['endpoint' => $endpoint, 'payload' => $data]
            );
        }

        $responseData = $response->json();

        if ($responseData === null && $response->body() !== 'null') {
            throw new StroUsdtException(
                'Invalid JSON response from Strowallet API',
                500,
                ['raw_response' => $response->body()]
            );
        }

        return $responseData;
    }


    public function createUsdtAddress(int $userId)
    {
        $mode = $this->mode;

        $webhookUrl = config('services.strowallet.webhook_url');
        if (empty($webhookUrl)) {
            throw new StroUsdtException('Strowallet webhook URL is not configured');
        }

        $data = [
            'public_key'   => $this->publicKey,
            'webhook_url'  => $webhookUrl,
        ];

        $response = $this->request(
            'post',
            "/generate-address/?mode={$mode}",
            $data,
            false
        );

        UsdtLogger::log("Strowallet API Raw Response", ['response' => $response]);

        $address = null;
        $network = 'TRC20';

        if (isset($response['data']['address'])) {
            $address = $response['data']['address'];
            $network = $response['data']['network'] ?? 'TRC20';
        } elseif (isset($response['data']['data']['address'])) {
            $address = $response['data']['data']['address'];
            $network = $response['data']['data']['network'] ?? 'TRC20';
        } elseif (isset($response['address'])) {
            $address = $response['address'];
            $network = $response['network'] ?? 'TRC20';
        }

        if (!$address) {
            UsdtLogger::log("No address found in Strowallet response", ['response' => $response]);
            throw new StroUsdtException('No address returned from Strowallet API');
        }

        // 🔥 Update or Create wallet
        $wallet = UsdtWallet::updateOrCreate(
            [
                'user_id' => $userId,
                'mode'    => $mode,
            ],
            [
                'address' => $address,
                'network' => $network,
            ]
        );

        $response['data'] = array_merge($response['data'] ?? [], $wallet->toArray());

        return [
            'success' => true,
            'message' => $wallet->wasRecentlyCreated
                ? 'Wallet created successfully'
                : 'Wallet updated successfully',
            'data'    => $wallet->toArray(),
        ];
    }


    public function getUsdtHistory(int $userId): array
    {
        $mode = $this->mode;

        $wallet = UsdtWallet::where('user_id', $userId)
            ->where('mode', $mode)
            ->lockForUpdate()->first();

        if (!$wallet) {
            throw new StroUsdtException("No USDT wallet found for this user");
        }

        $transactions = UsdtTransaction::where('usdt_wallet_id', $wallet->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'success' => true,
            'message' => 'History retrieved successfully',
            'data' => [
                'wallet' => $wallet,
                'transactions' => $transactions,
            ],
        ];
    }


    public function sendUsdt(string $toAddress, float $amount)
    {
        $mode = $this->mode;

        $data = [
            'to_address' => $toAddress,
            'amount' => $amount,
            'public_key' => $this->publicKey,
            'vip_key' => $mode === 'sandbox' ? 'test-vip-key' : config('services.strowallet.vip_key'),
        ];

        return $this->request(
            'post',
            "/send-usdt/?mode={$mode}",
            $data,
            true
        );
    }

    /**
     * Process webhook payload from Strowallet.
     *
     * @param array $payload
     * @return array
     */
    public function process(array $payload): array
    {
        UsdtLogger::log('Processing Strowallet Webhook:', $payload);

        if (
            ($payload['type'] ?? null) !== 'credit' ||
            ($payload['action'] ?? null) !== 'receive_usdt'
        ) {
            return [
                'success' => false,
                'message' => 'Not a credit transaction',
                'data' => []
            ];
        }

        $address = $payload['address'] ?? null;
        $amount = $payload['amount'] ?? null;

        if (!$address || !$amount) {
            return [
                'success' => false,
                'message' => 'Invalid payload: missing address or amount',
                'data' => []
            ];
        }

        $wallet = UsdtWallet::where('address', $address)->lockForUpdate()->first();

        if (!$wallet) {
            return [
                'success' => false,
                'message' => 'Wallet not found for address',
                'data' => []
            ];
        }

        $wallet->balance = bcadd($wallet->balance, $amount, 8);
        $wallet->last_synced_at = now();
        $wallet->save();

        UsdtTransaction::log($wallet, $payload);

        return [
            'success' => true,
            'message' => 'Wallet credited successfully',
            'data' => [
                'wallet_id' => $wallet->id,
                'new_balance' => $wallet->balance,
            ]
        ];
    }
    public function getExchangeRate(string $from, string $to): array
    {
        try {
            if ($from === 'USD' && $to === 'NGN') {
                $response = $this->request('get', '/exchange-rate/USD/NGN/', [], false);

                $rate = $response['rate'] ?? null;

                if (!$rate) {
                    throw new \Exception("Invalid rate response");
                }

                return [
                    'success' => true,
                    'message' => "Exchange rate USD to NGN retrieved successfully",
                    'data'    => [
                        'from' => 'USD',
                        'to'   => 'NGN',
                        'rate' => $rate,
                    ],
                ];
            }

            if ($from === 'NGN' && $to === 'USD') {
                $response = $this->request('get', '/exchange-rate/USD/NGN/', [], false);

                $usdToNgn = $response['rate'] ?? null;

                if (!$usdToNgn) {
                    throw new \Exception("Invalid rate response");
                }

                $rate = 1 / $usdToNgn;

                return [
                    'success' => true,
                    'message' => "Exchange rate NGN to USD retrieved successfully",
                    'data'    => [
                        'from' => 'NGN',
                        'to'   => 'USD',
                        'rate' => $rate,
                    ],
                ];
            }

            return [
                'success' => false,
                'message' => "Unsupported currency pair",
                'data'    => [],
            ];

        } catch (\Exception $e) {
            UsdtLogger::log("Exchange Rate API Error", [
                'error' => $e->getMessage(),
                'from'  => $from,
                'to'    => $to,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to fetch exchange rate',
                'data'    => []
            ];
        }
    }

    public function convertCurrency(int $userId, float $amount, string $direction = 'USDT_TO_NGN'): array
    {
        $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
        $usdtWallet = UsdtWallet::where('user_id', $userId)->lockForUpdate()->first();

        if (!$wallet || !$usdtWallet) {
            return [
                'success' => false,
                'message' => 'Wallets not found for user',
                'data' => []
            ];
        }

        $rateResponse = $this->getExchangeRate('USD', 'NGN');
        $rate = $rateResponse['data']['rate'] ?? null;

        if (!$rate) {
            return [
                'success' => false,
                'message' => 'Unable to fetch exchange rate',
                'data' => []
            ];
        }

        if ($direction === 'USDT_TO_NGN') {
            if ($usdtWallet->balance < $amount) {
                return [
                    'success' => false,
                    'message' => 'Insufficient USDT balance',
                    'data' => []
                ];
            }

            $converted = $amount * $rate;

            $walletBalanceBefore = $wallet->amount;
            $usdtBalanceBefore = $usdtWallet->balance;

            $usdtWallet->decrement('balance', $amount);
            $wallet->increment('amount', $converted);

            $wallet->refresh();
            $usdtWallet->refresh();

            UsdtTransaction::create([
                'usdt_wallet_id' => $usdtWallet->id,
                'type' => 'debit',
                'action' => 'convert_to_ngn',
                'amount' => $amount,
                'description' => "Converted $amount USDT to ₦$converted",
            ]);

            TransactionLog::create_transaction([
                'service_type' => 'conversion',
                'amount' => $converted,
                'amount_before' => $walletBalanceBefore,
                'amount_after' => $wallet->amount,
                'status' => 'success',
                'wallet_id' => $wallet->id,
                'provider' => 'Strowallet',
                'type' => 'credit',
                'description' => "Converted $amount USDT to ₦$converted",
            ]);

            return [
                'success' => true,
                'message' => 'Conversion successful',
                'data' => [
                    'direction' => $direction,
                    'rate' => $rate,
                    'usdt_amount' => $amount,
                    'ngn_amount' => $converted,
                    'wallet_balance' => $wallet->amount,
                    'usdt_balance' => $usdtWallet->balance,
                ]
            ];
        }

        if ($direction === 'NGN_TO_USDT') {
            if ($wallet->amount < $amount) {
                return [
                    'success' => false,
                    'message' => 'Insufficient NGN balance',
                    'data' => []
                ];
            }

            $converted = $amount / $rate;

            $walletBalanceBefore = $wallet->amount;
            $usdtBalanceBefore = $usdtWallet->balance;

            $wallet->decrement('amount', $amount);
            $usdtWallet->increment('balance', $converted);

            $wallet->refresh();
            $usdtWallet->refresh();

            TransactionLog::create_transaction([
                'service_type' => 'conversion',
                'amount' => $amount,
                'amount_before' => $walletBalanceBefore,
                'amount_after' => $wallet->amount,
                'status' => 'success',
                'wallet_id' => $wallet->id,
                'provider' => 'Strowallet',
                'type' => 'debit',
                'description' => "Converted ₦$amount to $converted USDT",
            ]);

            UsdtTransaction::create([
                'usdt_wallet_id' => $usdtWallet->id,
                'type' => 'credit',
                'action' => 'convert_from_ngn',
                'amount' => $converted,
                'description' => "Converted ₦$amount to $converted USDT",
            ]);

            return [
                'success' => true,
                'message' => 'Conversion successful',
                'data' => [
                    'direction' => $direction,
                    'rate' => $rate,
                    'ngn_amount' => $amount,
                    'usdt_amount' => $converted,
                    'wallet_balance' => $wallet->amount,
                    'usdt_balance' => $usdtWallet->balance,
                ]
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid conversion direction',
            'data' => []
        ];
    }


    /**
     * Fund a virtual card using the authenticated user's USDT balance.
     * @throws \Throwable
     */
    public function fundFromUsdt(float $amount): array
    {
        $user = Auth::user();

        return DB::transaction(function () use ($user, $amount) {
            $usdtWallet = UsdtWallet::where('user_id', $user->id)->lockForUpdate()->first();
            $card       = VirtualCard::where('user_id', $user->id)->lockForUpdate()->first();

            if (!$usdtWallet || !$card) {
                throw new StroUsdtException('USDT wallet or Virtual Card not found for user.');
            }

            if (bccomp($usdtWallet->balance, $amount, 8) < 0) {
                throw new StroUsdtException('Insufficient USDT balance.');
            }
            $cardBalanceBefore   = $card->balance;
            $usdtWallet->balance = bcsub($usdtWallet->balance, $amount, 8);
            $usdtWallet->save();

            $card->balance = bcadd($card->balance, $amount, 8);
            $card->save();

            UsdtTransaction::logTransaction(
                $usdtWallet->id,
                [
                    'type'        => 'debit',
                    'action'      => 'fund_virtual_card',
                    'amount'      => $amount,
                    'description' => "Funded virtual card #{$card->id} with {$amount} USDT",
                ]
            );

            TransactionLog::create_transaction([
                'service_type'   => 'fund_virtual_card',
                'amount'         => $amount,
                'amount_before'  => $cardBalanceBefore,
                'amount_after'   => $card->balance,
                'status'         => 'success',
                'wallet_id'      => $card->id,
                'provider'       => 'Strowallet',
                'type'           => 'credit',
                'description'    => "Funded from USDT wallet #{$usdtWallet->id}",
            ]);

            return [
                'success' => true,
                'message' => 'Virtual card funded successfully',
                'data'    => [
                    'usdt_balance'  => $usdtWallet->balance,
                    'card_balance'  => $card->balance,
                    'funded_amount' => $amount,
                    'card_id'       => $card->id,
                ],
            ];
        });
    }

    /**
     * Buy ePIN from Strowallet API
     *
     * @throws StroUsdtException
     */
    public function buyEpin(string $cardNetwork, float $value, int $quantity): array
    {
        $mode = $this->mode;
        $endpoint = "/buy_epin/";

        $payload = [
            'public_key'   => $this->publicKey,
            'card_network' => $cardNetwork,
            'value'        => $value,
            'quantity'     => $quantity,
            'mode'         => $mode,
        ];

        return $this->request('post', $endpoint, $payload, false);
    }

    /**
     * Get all PDF files for a specific user
     */
    public function getUserPdfFiles(int $userId): Collection
    {
        $files = Storage::files('epins');

        return collect($files)->filter(function ($file) use ($userId) {
            return preg_match("/^epins\/epin_receipt_{$userId}_(.+)\.pdf$/", $file);
        })->map(function ($file) use ($userId) {
            preg_match("/^epins\/epin_receipt_{$userId}_(.+)\.pdf$/", $file, $matches);
            $transactionId = $matches[1] ?? 'unknown';

            return [
                'file_path' => $file,
                'file_name' => basename($file),
                'transaction_id' => $transactionId,
                'file_size' => Storage::size($file),
                'last_modified' => Storage::lastModified($file),
                'created_date' => date('Y-m-d H:i:s', Storage::lastModified($file)),
                'file_url' => Storage::url($file),
            ];
        })->sortByDesc('last_modified')->values();
    }

    /**
     * Debit the wallet and create a transaction log
     */
    public function debitWalletAndCreateLog(int $userId, float $amount, string $trxid): array
    {
        return DB::transaction(function () use ($userId, $amount, $trxid) {
            $wallet = Wallet::where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$wallet || $wallet->amount < $amount) {
                return [
                    'success' => false,
                    'message' => 'Wallet check failed during debit',
                ];
            }

            $walletBalanceBefore = $wallet->amount;
            $wallet->decrement('amount', $amount);
            $wallet->refresh();

            $transaction = TransactionLog::create_transaction([
                'service_type'          => 'Epin_payment',
                'amount'                => $amount,
                'amount_before'         => $walletBalanceBefore,
                'amount_after'          => $wallet->amount,
                'status'                => 'success',
                'wallet_id'             => $wallet->id,
                'provider'              => 'Strowallet',
                'type'                  => 'debit',
                'description'           => "Epin Buying payment of $trxid",
            ]);

            Log::info("Wallet debited and transaction created", [
                'user_id' => $userId,
                'amount'  => $amount,
                'transaction_created' => (bool)$transaction
            ]);

            return [
                'success' => true,
                'message' => 'Wallet debited and transaction logged',
                'data'    => [
                    'new_balance' => $wallet->amount,
                    'amount'      => $amount,
                ]
            ];
        });
    }

    /**
     * Generate PDF from stored epin data - Option 1
     */
    public function generateEpinPdf($user, array $epinData): string|false
    {
        try {
            $this->validateEpinData($epinData);

            $pdf = Pdf::loadView('pdfs.epin_receipt', [
                'user'     => $user,
                'epinData' => $epinData,
            ])
                ->setPaper('a4', 'portrait');

            $timestamp = now()->format('Y-m-d_H-i-s');
            $fileName = "epins/epin_receipt_{$user->id}_{$epinData['trxid']}_{$timestamp}.pdf";

            Storage::put($fileName, $pdf->output());

            Log::info('Epin PDF generated successfully', [
                'user_id' => $user->id,
                'transaction_id' => $epinData['trxid'],
                'file_path' => $fileName
            ]);

            return $fileName;

        } catch (\Exception $e) {
            Log::error('Failed to generate Epin PDF', [
                'user_id' => $user->id ?? null,
                'transaction_id' => $epinData['trxid'] ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Send epin email with PDF attachment
     */
    public function sendEpinEmail($user, array $epinData): bool
    {
        try {
            $pdfPath = $this->generateEpinPdf($user, $epinData);

            if (!$pdfPath || !Storage::exists($pdfPath)) {
                throw new \Exception("Failed to generate PDF");
            }

            $pdfContent = Storage::get($pdfPath);

            Mail::send('emails.epin_receipt', [
                'user' => $user,
                'epinData' => $epinData
            ], function ($message) use ($user, $pdfContent, $pdfPath, $epinData) {
                $message->to($user->email, $user->name)
                    ->subject("Your {$epinData['card_network']} Epin Cards - Transaction #{$epinData['trxid']}")
                    ->attachData($pdfContent, basename($pdfPath), [
                        'mime' => 'application/pdf',
                    ]);
            });

            Log::info('Epin email sent successfully', [
                'user_id' => $user->id,
                'transaction_id' => $epinData['trxid'],
                'pdf_path' => $pdfPath
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send Epin email', [
                'user_id' => $user->id ?? null,
                'transaction_id' => $epinData['trxid'] ?? null,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Validate epin data structure
     */
    private function validateEpinData(array $epinData): void
    {
        $required = ['card_network', 'value', 'quantity', 'cards', 'trxid'];

        foreach ($required as $field) {
            if (!isset($epinData[$field])) {
                throw new \InvalidArgumentException("Missing required field: {$field}");
            }
        }

        if (!is_array($epinData['cards']) || empty($epinData['cards'])) {
            throw new \InvalidArgumentException("Cards array is required and cannot be empty");
        }

        foreach ($epinData['cards'] as $index => $card) {
            if (!isset($card['pin']) || !isset($card['serial'])) {
                throw new \InvalidArgumentException("Card at index {$index} missing pin or serial");
            }
        }

        if (count($epinData['cards']) != $epinData['quantity']) {
            throw new \InvalidArgumentException("Card count mismatch: expected {$epinData['quantity']}, got " . count($epinData['cards']));
        }
    }


}
