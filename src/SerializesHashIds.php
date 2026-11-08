<?php

declare(strict_types=1);

namespace TobyMaxham\HashId;

use Illuminate\Database\Eloquent\Model;
use TobyMaxham\HashId\Facades\IdHasher;

/**
 * Replaces the primary key (and mapped foreign keys) with hashes in toArray() and JSON.
 * Use it together with the Hasher trait.
 *
 * Foreign keys: override hashIdForeignKeys() or define a $hashIdForeignKeys property,
 * e.g. ['product_id' => Product::class].
 *
 * @mixin Model
 * @mixin Hasher
 */
trait SerializesHashIds
{
    #[\Override]
    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();

        $key = $this->getKeyName();

        if ($this->isHashableId($attributes[$key] ?? null)) {
            $attributes[$key] = $this->hashIdManager()->encodeId($attributes[$key]);
        }

        foreach ($this->hashIdForeignKeys() as $column => $model) {
            if ($this->isHashableId($attributes[$column] ?? null)) {
                $attributes[$column] = IdHasher::for($model)->encodeId($attributes[$column]);
            }
        }

        return $attributes;
    }

    /**
     * Some database drivers return integers as strings, e.g. "5".
     */
    protected function isHashableId(mixed $value): bool
    {
        return is_int($value) || (is_string($value) && ctype_digit($value));
    }

    /**
     * @return array<string, class-string<Model>|string>
     */
    public function hashIdForeignKeys(): array
    {
        return property_exists($this, 'hashIdForeignKeys') ? $this->hashIdForeignKeys : [];
    }
}
