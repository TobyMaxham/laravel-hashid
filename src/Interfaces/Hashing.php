<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Interfaces;

interface Hashing
{
    /**
     * @return string|null
     */
    public function hashID();
}
