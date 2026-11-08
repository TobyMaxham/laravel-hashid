<?php

return [
    /*
     | Prefix for every hash, e.g. "h:" -> "h:J7dVgYxKPwyQejOMnL".
     | Set HASH_PREFIX= (empty) or HASH_PREFIX=null to use no prefix at all.
     */
    'prefix' => env('HASH_PREFIX', 'h:'),

    /*
     | "strict":   a hash must start with the prefix (default).
     | "optional": a hash is accepted with and without the prefix,
     |             e.g. "h:J7dVgYxKPwyQejOMnL" and "J7dVgYxKPwyQejOMnL" (see safeHashID()).
     | Hashes are always generated with the prefix.
     */
    'prefix_mode' => env('HASH_PREFIX_MODE', 'strict'),

    'salt'              => env('HASH_SALT', 'your-salt-string'),
    'length'            => env('HASH_LENGTH', 18),
    'alphabet'          => env('HASH_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
    'exception'         => \TobyMaxham\HashId\Exceptions\WrongIdException::class,
    'exception_message' => 'WrongIdException',
    'disable_exception' => false,
];
