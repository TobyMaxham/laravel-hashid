<?php

namespace TobyMaxham\HashId\Tests\Fixtures;

class KeyedByProperty extends Product
{
    protected string $hashIdKey = 'renamed-products';

    protected ?string $hashIdPrefix = '';

    protected $table = 'products';
}
