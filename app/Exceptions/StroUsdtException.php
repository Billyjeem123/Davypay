<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class StroUsdtException extends Exception
{
    protected array $context;

    /**
     * StroUsdtException constructor.
     *
     * @param string $message
     * @param int $code
     * @param array $context  Extra context like endpoint, payload, or response body
     * @param Throwable|null $previous
     */
    public function __construct(string $message, int $code = 0, array $context = [], Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->context = $context;
    }

    /**
     * Get extra context (useful for logging/debugging).
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
