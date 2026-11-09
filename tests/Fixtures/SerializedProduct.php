<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Tests\Fixtures;

use TobyMaxham\HashId\SerializesHashIds;

class SerializedProduct extends Product
{
    use SerializesHashIds;

    protected $table = 'products';
}
