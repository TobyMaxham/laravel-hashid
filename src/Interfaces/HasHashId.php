<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Interfaces;

use TobyMaxham\HashId\IdHasherManager;

/**
 * Implemented by models that use the Hasher trait.
 * Extends Hashing, so checks for Hashing keep working.
 */
interface HasHashId extends Hashing
{
    public function hashID(): ?string;

    public function safeHashID(): ?string;

    public function hashIdKey(): string;

    public function hashIdPrefix(): ?string;

    public function hashIdManager(): IdHasherManager;
}
