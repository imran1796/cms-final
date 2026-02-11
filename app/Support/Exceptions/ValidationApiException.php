<?php

namespace App\Support\Exceptions;

final class ValidationApiException extends ApiException
{
    public function __construct(string $message = 'Validation failed', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 422, 'VALIDATION_ERROR', $errors, $previous);
    }
}
