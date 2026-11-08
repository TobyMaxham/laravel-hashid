<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Exceptions;

class WrongIdException extends \Exception
{
    public function __construct($message = 'WrongIdException')
    {
        parent::__construct($message);
    }
}
