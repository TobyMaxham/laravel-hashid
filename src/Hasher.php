<?php

namespace TobyMaxham\HashId;

use TobyMaxham\HashId\Facades\IdHasher;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Hasher
{
    public function getRouteKey()
    {
        return $this->hashIdManager()->encodeId(parent::getRouteKey());
    }

    /**
     * Added to the salt, so every model gets its own hashes (see hashids.per_model).
     * Override this method, or define a HASH_ID_KEY constant or a $hashIdKey property.
     * Defaults to the table name. Changing it changes all hashes of the model.
     */
    public function hashIdKey(): string
    {
        if (defined(static::class.'::HASH_ID_KEY')) {
            return (string) constant(static::class.'::HASH_ID_KEY');
        }

        if (property_exists($this, 'hashIdKey')) {
            return (string) $this->hashIdKey;
        }

        return $this->getTable();
    }

    /**
     * Prefix for this model, e.g. "usr_". null uses hashids.prefix, '' disables the prefix.
     * Override this method, or define a HASH_ID_PREFIX constant or a $hashIdPrefix property.
     */
    public function hashIdPrefix(): ?string
    {
        if (defined(static::class.'::HASH_ID_PREFIX')) {
            return constant(static::class.'::HASH_ID_PREFIX');
        }

        if (property_exists($this, 'hashIdPrefix')) {
            return $this->hashIdPrefix;
        }

        return null;
    }

    public function hashIdManager(): IdHasherManager
    {
        return IdHasher::for($this);
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
        return $this->hashIdManager()->decodeId($value, $throw);
    }

    public function hashID(): ?string
    {
        return $this->getRouteKey();
    }

    /**
     * The hash without prefix. Accepted in routes when hashids.prefix_mode is "optional".
     */
    public function safeHashID(): ?string
    {
        return $this->hashIdManager()->encodeIdWithoutPrefix(parent::getRouteKey());
    }
}
