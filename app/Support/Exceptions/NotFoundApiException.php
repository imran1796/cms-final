<?php

namespace App\Support\Exceptions;

final class NotFoundApiException extends ApiException
{
    public function __construct(string $message = 'Resource not found', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 404, 'NOT_FOUND', $errors, $previous);
    }
}
