<?php

namespace App\Support\Exceptions;

final class DomainApiException extends ApiException
{
    public function __construct(string $message = 'Domain rule violated', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, 409, 'DOMAIN_ERROR', $errors, $previous);
    }
}
