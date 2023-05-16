<?php

return [
    'prefix'   => env('HASH_PREFIX', 'h:'),
    'salt'     => env('HASH_SALT', 'your-salt-string'),
    'length'   => env('HASH_LENGTH', 18),
    'alphabet' => env('HASH_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
];
