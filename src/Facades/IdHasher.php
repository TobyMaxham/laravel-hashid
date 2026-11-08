<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Facades;

use Illuminate\Support\Facades\Facade;
use TobyMaxham\HashId\IdHasherManager;

/**
 * @method static \TobyMaxham\HashId\IdHasherManager for(\Illuminate\Database\Eloquent\Model|string $model)
 * @method static string encodeId(int|string $id)
 * @method static string encodeIdWithoutPrefix(int|string $id)
 * @method static int|null decodeId(mixed $hash, bool $throw = true)
 * @method static string prefix()
 * @method static string prefixMode()
 *
 * @see IdHasherManager
 */
class IdHasher extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'idhasher';
    }
}
