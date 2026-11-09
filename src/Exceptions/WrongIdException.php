<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Exceptions;

/**
 * @deprecated Catch or throw InvalidHashIdException instead.
 */
class WrongIdException extends \Exception
{
    public function __construct(string $message = 'WrongIdException')
    {
        parent::__construct($message);
    }
}
