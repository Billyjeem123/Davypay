<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyStrowalletWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $signature = $request->header('X-Strowallet-Signature');

        if (!$signature) {
            return $next($request);
        }

        $payload = $request->getContent();
        $secret = config('services.strowallet.secret');

        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            Log::error('Invalid webhook signature', [
                'expected' => $expectedSignature,
                'received' => $signature
            ]);

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        return $next($request);
    }
}
