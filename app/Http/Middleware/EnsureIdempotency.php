<?php

namespace App\Http\Middleware;

use App\Helpers\Utility;
use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureIdempotency
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(Request): (Response)  $next
     */

    public function handle001(Request $request, Closure $next, $required = 'false'): Response
    {
        #  1. Apply only to non-safe methods
        if (!in_array($request->method(), ['POST', 'GET'])) {
            return $next($request);
        }

        $key = $request->header('idempotency_key');

        #  2. Check if idempotency key is required but missing
        $isRequired = filter_var($required, FILTER_VALIDATE_BOOLEAN);

        if ($isRequired && empty($key)) {
            return response()->json([
                'status' => false,
                'message' => 'Idempotency-Key header is required for this endpoint.'
            ], 400); #  400 Bad Request
        }

        #  3. If key is not required and not provided, skip idempotency logic
        if (is_null($key)) {
            return $next($request);
        }

        #  4. Continue with idempotency logic if key is provided...
        $cacheKey = 'idempotency:' . $key;
        $lockKey = $cacheKey . ':lock';
        $request->attributes->set('idempotency_key', $key);

        $lock = Cache::lock($lockKey, 10);

        try {
            $lock->block(10);

            if (Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                return response()->json(json_decode($cached['content'], true), $cached['status'], $cached['headers']);
            }

            $response = $next($request);

            if ($response->isSuccessful()) {
                $storeData = [
                    'content' => $response->getContent(),
                    'status' => $response->status(),
                    'headers' => $response->headers->all()
                ];
                Cache::put($cacheKey, $storeData, now()->addHours(24));
            }

            return $response;

        } catch (LockTimeoutException $e) {
            Log::error("Idempotency lock timeout for key: $key", ['exception' => $e]);
            return response()->json(['message' => 'Request timeout due to conflict.'], 409);
        } finally {
            optional($lock)->release();
        }
    }

    public function handle(Request $request, Closure $next, $required = 'false'): Response
    {
        #  1. Apply only to non-safe methods
        if (!in_array($request->method(), ['POST', 'GET'])) {
            return $next($request);
        }

        $key = $request->header('idempotency_key');

        #  2. Check if idempotency key is required but missing
        $isRequired = filter_var($required, FILTER_VALIDATE_BOOLEAN);

        if ($isRequired && empty($key)) {
            return response()->json([
                'status' => false,
                'message' => 'Idempotency-Key header is required for this endpoint.'
            ], 400); #  400 Bad Request
        }

        #  3. If key is not required and not provided, skip idempotency logic
        if (is_null($key)) {
            return $next($request);
        }

        #  4. Continue with idempotency logic if key is provided...
        $cacheKey = 'idempotency:' . $key;
        $lockKey = $cacheKey . ':lock';
        $request->attributes->set('idempotency_key', $key);

        $lock = Cache::lock($lockKey, 10);

        try {
            $lock->block(10);

            #  Return cached response if it exists
            if (Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                return response()->json(
                    json_decode($cached['content'], true),
                    $cached['status'],
                    $cached['headers']
                );
            }

            #  Execute the request
            $response = $next($request);

            #  Only cache if response body has status:true or success:true
            $responseData = json_decode($response->getContent(), true);
            $shouldCache = isset($responseData['status']) && $responseData['status'] === true
                || isset($responseData['success']) && $responseData['success'] === true;

            if ($shouldCache) {
                $storeData = [
                    'content' => $response->getContent(),
                    'status' => $response->status(),
                    'headers' => $response->headers->all()
                ];
                Cache::put($cacheKey, $storeData, now()->addHours(24));
            }

            return $response;

        } catch (LockTimeoutException $e) {
            Log::error("Idempotency lock timeout for key: $key", ['exception' => $e]);
            return response()->json(['message' => 'Request timeout due to conflict.'], 409);
        } finally {
            optional($lock)->release();
        }
    }

}
