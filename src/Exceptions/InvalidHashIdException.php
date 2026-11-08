<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Exceptions;

/**
 * Thrown by IdHasherManager::decodeId() for invalid hashes.
 * Extends WrongIdException, so existing catch blocks keep working.
 */
class InvalidHashIdException extends WrongIdException
{
    public function __construct(string $message = 'The given hash id is invalid.')
    {
        parent::__construct($message);
    }
}
