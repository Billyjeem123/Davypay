<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class KycLogger
{

    public  static function log(string $message, array $context = []): void
    {
        Log::channel('kyc')->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        Log::channel('kyc')->error($message, $context);
    }

    public static  function debug(string $message, array $context = []): void
    {
        Log::channel('kyc')->debug($message, $context);
    }
}
