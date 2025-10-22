<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class EsimLogger
{

    public  static function log(string $message, array $context = []): void
    {
        Log::channel('esim')->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        Log::channel('esim')->error($message, $context);
    }

    public static  function debug(string $message, array $context = []): void
    {
        Log::channel('esim')->debug($message, $context);
    }
}
