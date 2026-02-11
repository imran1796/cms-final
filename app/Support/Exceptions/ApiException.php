<?php

namespace App\Support\Exceptions;

use RuntimeException;

abstract class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        protected int $status = 400,
        protected ?string $codeString = null,
        protected array $errors = [],
        ?\Throwable $previous = null,
        protected array $meta = []
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function codeString(): ?string
    {
        return $this->codeString;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function meta(): array
    {
        return $this->meta;
    }
}
