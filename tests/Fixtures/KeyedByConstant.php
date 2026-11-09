<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Tests\Fixtures;

class KeyedByConstant extends Product
{
    public const HASH_ID_KEY = 'renamed-products';

    public const HASH_ID_PREFIX = 'prd_';

    protected $table = 'products';
}
