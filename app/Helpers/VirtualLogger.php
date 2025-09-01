<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class VirtualLogger
{

    public  static function log(string $message, array $context = []): void
    {
        Log::channel('virtual_cards')->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        Log::channel('virtual_cards')->error($message, $context);
    }

    public static  function debug(string $message, array $context = []): void
    {
        Log::channel('virtual_cards')->debug($message, $context);
    }
}
