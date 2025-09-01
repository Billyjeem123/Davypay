<?php

namespace App\Exceptions;

use Exception;

class TransferException extends Exception
{
    /**
     * The HTTP status code to use for the response.
     *
     * @var int
     */
    protected $statusCode;

    /**
     * The validation error messages.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * The error bag name.
     *
     * @var string
     */
    protected $errorBag = 'default';

    public function __construct($message = "", $statusCode = 400, Exception $previous = null)
    {
        $this->statusCode = $statusCode;
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Create a new transfer exception from a plain array of messages.
     *
     * @param array $messages
     * @param int $statusCode
     * @return static
     */
    public static function withMessages(array $messages, int $statusCode = 422)
    {
        $exception = new static('The given data was invalid.', $statusCode);
        $exception->errors = $messages;

        return $exception;
    }

    /**
     * Create a new transfer exception for insufficient balance.
     *
     * @param float $required
     * @param float $available
     * @return static
     */
    public static function insufficientBalance(float $required, float $available)
    {
        return static::withMessages([
            'amount' => "Insufficient available balance. Required: ₦" . number_format($required, 2) .
                ", Available: ₦" . number_format($available, 2)
        ], 400);
    }

    /**
     * Create a new transfer exception for wallet not found.
     *
     * @return static
     */
    public static function walletNotFound()
    {
        return static::withMessages([
            'wallet' => 'Wallet not found for this user.'
        ], 404);
    }

    /**
     * Create a new transfer exception for invalid transfer data.
     *
     * @param array $fieldErrors
     * @return static
     */
    public static function invalidTransferData(array $fieldErrors)
    {
        return static::withMessages($fieldErrors, 422);
    }

    /**
     * Create a new transfer exception for duplicate reference.
     *
     * @param string $reference
     * @return static
     */
    public static function duplicateReference(string $reference)
    {
        return static::withMessages([
            'reference' => "Duplicate transfer reference detected: {$reference}"
        ], 409);
    }

    /**
     * Get the HTTP status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set the HTTP status code.
     *
     * @param int $statusCode
     * @return $this
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Get all of the validation error messages.
     *
     * @return array
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Check if the exception has validation errors.
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get error messages for a specific field.
     *
     * @param string $field
     * @return array
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Set the error bag name.
     *
     * @param string $errorBag
     * @return $this
     */
    public function errorBag(string $errorBag): self
    {
        $this->errorBag = $errorBag;
        return $this;
    }

    /**
     * Get the error bag name.
     *
     * @return string
     */
    public function getErrorBag(): string
    {
        return $this->errorBag;
    }

    /**
     * Convert the exception to an array for JSON responses.
     *
     * @return array
     */
    public function toArray(): array
    {
        $response = [
            'success' => false,
            'message' => $this->getMessage(),
            'status_code' => $this->statusCode,
        ];

        if ($this->hasErrors()) {
            $response['errors'] = $this->errors;
        }

        return $response;
    }

}
