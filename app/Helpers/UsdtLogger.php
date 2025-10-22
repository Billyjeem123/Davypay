<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class UsdtLogger
{
    /**
     * Get the appropriate logger channel
     */
    private static function getLogger()
    {
        if (Config::has('logging.channels.usdt')) {
            return Log::channel('usdt');
        }
        return Log::channel();
    }

    public static function log(string $message, array $context = []): void
    {
        self::getLogger()->info("[USDT] {$message}", $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getLogger()->error("[USDT] {$message}", $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::getLogger()->debug("[USDT] {$message}", $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getLogger()->warning("[USDT] {$message}", $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::getLogger()->critical("[USDT] {$message}", $context);
    }
}
