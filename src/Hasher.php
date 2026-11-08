<?php

namespace TobyMaxham\HashId;

use Illuminate\Support\Str;
use TobyMaxham\HashId\Facades\IdHasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Hasher
{
    public function getRouteKey()
    {
        return IdHasher::encodeId(parent::getRouteKey());
    }

    /**
     * Laravel uses this query for normal, scoped (child) and withTrashed() route bindings.
     * Only the route key is decoded, custom fields like {post:slug} stay untouched.
     *
     * @throws ModelNotFoundException
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        if ($this->isHashedRouteField($field)) {
            $id = $this->decodeId($value, false);

            if (null === $id) {
                throw (new ModelNotFoundException)->setModel(static::class, $value);
            }

            $value = $id;
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }

    protected function isHashedRouteField(?string $field): bool
    {
        if (null === $field || '' === $field) {
            return true;
        }

        return in_array($field, [$this->getRouteKeyName(), $this->qualifyColumn($this->getRouteKeyName())], true);
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
