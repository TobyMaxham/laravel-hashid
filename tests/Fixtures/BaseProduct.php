<?php

namespace TobyMaxham\HashId\Tests\Fixtures;

use TobyMaxham\HashId\ModelWithHasher;

class BaseProduct extends ModelWithHasher
{
    public $timestamps = false;

    protected $table = 'products';

    protected $guarded = [];
}
