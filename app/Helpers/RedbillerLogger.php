<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class RedbillerLogger
{

    public  static function log(string $message, array $context = []): void
    {
        Log::channel('redbiller')->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        Log::channel('redbiller')->error($message, $context);
    }

    public static  function debug(string $message, array $context = []): void
    {
        Log::channel('redbiller')->debug($message, $context);
    }
}
