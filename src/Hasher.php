<?php

namespace TobyMaxham\HashId;

use Illuminate\Support\Str;
use TobyMaxham\HashId\Facades\IdHasher;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Hasher
{
    public function getRouteKey()
    {
        return IdHasher::encodeId(parent::getRouteKey());
    }

    public function resolveRouteBinding($value, $field = null): ?\Illuminate\Database\Eloquent\Model
    {
        if (! $id = $this->decodeId($value, false)) {
            return null;
        }

        return parent::resolveRouteBinding($id, $field);
    }

    protected function decodeId($value, bool $throw = true)
    {
        return IdHasher::decodeId($value, $throw);
    }

    public function hashID(): ?string
    {
        return $this->getRouteKey();
    }

    public function safeHashID(): ?string
    {
        return Str::of($this->hashID())->after(config('hashids.prefix'));
    }
}
