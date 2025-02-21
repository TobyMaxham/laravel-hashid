<?php

return [
    'prefix'            => env('HASH_PREFIX', 'h:'),
    'salt'              => env('HASH_SALT', 'your-salt-string'),
    'length'            => env('HASH_LENGTH', 18),
    'alphabet'          => env('HASH_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
    'exception'         => \TobyMaxham\HashId\Exceptions\WrongIdException::class,
    'exception_message' => 'WrongIdException',
    'disable_exception' => false,
];
