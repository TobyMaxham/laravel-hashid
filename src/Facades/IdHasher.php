<?php

namespace TobyMaxham\HashId\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string encodeId(int|string $id)
 * @method static int decodeId(string $hash, bool $throw = true)
 */
class IdHasher extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'idhasher';
    }
}
