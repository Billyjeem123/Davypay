<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class AccountManagerLogger
{

    public  static function log(string $message, array $context = []): void
    {
        Log::channel('account_manager')->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        Log::channel('account_manager')->error($message, $context);
    }

    public static  function debug(string $message, array $context = []): void
    {
        Log::channel('account_manager')->debug($message, $context);
    }
}
