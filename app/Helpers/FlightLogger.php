<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class FlightLogger
{
    /**
     * Get the appropriate logger channel
     */
    private static function getLogger()
    {
        if (Config::has('logging.channels.flight')) {
            return Log::channel('flight');
        }
        return Log::channel();
    }

    public static function log(string $message, array $context = []): void
    {
        self::getLogger()->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::getLogger()->error($message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::getLogger()->debug($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::getLogger()->warning($message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::getLogger()->critical($message, $context);
    }
}
