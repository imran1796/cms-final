<?php

declare(strict_types=1);

namespace App\Support\Exceptions;

final class EntryLockedApiException extends ApiException
{
    public function __construct(
        string $message = 'Entry is locked by another user',
        array $meta = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 409, 'ENTRY_LOCKED', [], $previous, $meta);
    }
}
