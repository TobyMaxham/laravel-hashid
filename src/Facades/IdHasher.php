<?php

namespace TobyMaxham\HashId\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string encodeId(int|string $id)
 * @method static string encodeIdWithoutPrefix(int|string $id)
 * @method static int|null decodeId(mixed $hash, bool $throw = true)
 * @method static string prefix()
 * @method static string prefixMode()
 *
 * @see \TobyMaxham\HashId\IdHasherManager
 */
class IdHasher extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'idhasher';
    }
}
