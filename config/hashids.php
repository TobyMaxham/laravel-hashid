<?php

use TobyMaxham\HashId\Exceptions\InvalidHashIdException;

return [
    /*
     | Prefix for every hash, e.g. "h:" -> "h:J7dVgYxKPwyQejOMnL".
     | Set HASH_PREFIX= (empty) or HASH_PREFIX=null to use no prefix at all.
     | Allowed characters: a-z, A-Z, 0-9 and - . _ ~ :
     */
    'prefix' => env('HASH_PREFIX', 'h:'),

    /*
     | "strict":   a hash must start with the prefix (default).
     | "optional": a hash is accepted with and without the prefix,
     |             e.g. "h:J7dVgYxKPwyQejOMnL" and "J7dVgYxKPwyQejOMnL" (see safeHashID()).
     | Hashes are always generated with the prefix.
     */
    'prefix_mode' => env('HASH_PREFIX_MODE', 'strict'),

    'salt' => env('HASH_SALT', 'your-salt-string'),

    /*
     | Old salts, only used to decode. Encoding always uses "salt".
     | After changing the salt, add the old one here, so old links keep working:
     | HASH_PREVIOUS_SALTS="old-salt,older-salt"
     */
    'previous_salts' => env('HASH_PREVIOUS_SALTS', ''),

    /*
     | true:  every model gets its own hashes (the model key is added to the salt),
     |        so user 400 and post 400 have different hashes.
     | false: all models share the same hashes (behavior of 1.x).
     */
    'per_model' => env('HASH_PER_MODEL', true),

    'length'            => env('HASH_LENGTH', 18),
    'alphabet'          => env('HASH_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
    'exception'         => InvalidHashIdException::class,
    'exception_message' => 'The given hash id is invalid.',
    'disable_exception' => false,
];
