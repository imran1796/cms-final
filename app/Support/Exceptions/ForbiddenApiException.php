<?php

namespace App\Support\Exceptions;

final class ForbiddenApiException extends ApiException
{
    public function __construct(string $message = 'Forbidden', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, 'FORBIDDEN', $errors, $previous);
    }
}
