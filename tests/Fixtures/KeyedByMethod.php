<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Tests\Fixtures;

class KeyedByMethod extends Product
{
    protected $table = 'products';

    public function hashIdKey(): string
    {
        return 'renamed-products';
    }

    public function hashIdPrefix(): ?string
    {
        return 'm_';
    }
}
