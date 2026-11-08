<?php

declare(strict_types=1);

namespace TobyMaxham\HashId;

use Illuminate\Database\Eloquent\Model;
use TobyMaxham\HashId\Facades\IdHasher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @mixin Model
 */
trait Hasher
{
    #[\Override]
    public function getRouteKey(): mixed
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
     * Decode a hash of this model without a query. Returns null for invalid hashes.
     */
    public static function decodeHashId(mixed $hash): ?int
    {
        return IdHasher::for(static::class)->decodeId($hash, false);
    }

    public static function findByHashId(mixed $hash, array|string $columns = ['*']): ?static
    {
        if (null === $id = static::decodeHashId($hash)) {
            return null;
        }

        return static::query()->whereKey($id)->first($columns);
    }

    /**
     * @throws ModelNotFoundException
     */
    public static function findByHashIdOrFail(mixed $hash, array|string $columns = ['*']): static
    {
        return static::findByHashId($hash, $columns)
            ?? throw (new ModelNotFoundException)->setModel(static::class, [$hash]);
    }

    /**
     * Invalid hashes are skipped. No query is made when no hash is valid.
     */
    public static function findManyByHashIds(iterable $hashes, array|string $columns = ['*']): Collection
    {
        $ids = static::decodeHashIds($hashes);

        if ([] === $ids) {
            return (new static)->newCollection();
        }

        return static::query()->whereKey($ids)->get($columns);
    }

    /**
     * @return list<int>
     */
    public static function decodeHashIds(iterable $hashes): array
    {
        $ids = [];

        foreach ($hashes as $hash) {
            if (null !== $id = static::decodeHashId($hash)) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * An invalid hash matches no rows.
     */
    public function scopeWhereHashId(Builder $query, mixed $hash): void
    {
        $query->whereIn($this->getQualifiedKeyName(), static::decodeHashIds([$hash]));
    }

    public function scopeOrWhereHashId(Builder $query, mixed $hash): void
    {
        $query->orWhereIn($this->getQualifiedKeyName(), static::decodeHashIds([$hash]));
    }

    /**
     * Invalid hashes are skipped, so no valid hash matches no rows.
     */
    public function scopeWhereHashIdIn(Builder $query, iterable $hashes): void
    {
        $query->whereIn($this->getQualifiedKeyName(), static::decodeHashIds($hashes));
    }

    /**
     * Invalid hashes are skipped, so no valid hash excludes no rows.
     */
    public function scopeWhereHashIdNotIn(Builder $query, iterable $hashes): void
    {
        $query->whereNotIn($this->getQualifiedKeyName(), static::decodeHashIds($hashes));
    }

    public function scopeWhereHashIdNot(Builder $query, mixed $hash): void
    {
        $this->scopeWhereHashIdNotIn($query, [$hash]);
    }

    /**
     * Laravel uses this query for normal, scoped (child) and withTrashed() route bindings.
     * Only the route key is decoded, custom fields like {post:slug} stay untouched.
     *
     * @throws ModelNotFoundException
     */
    #[\Override]
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
