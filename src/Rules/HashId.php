<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Rules;

use Closure;
use TobyMaxham\HashId\IdHasherManager;
use Illuminate\Database\Eloquent\Model;
use TobyMaxham\HashId\Facades\IdHasher;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The value is a valid hash of the model (or the global hasher without a model).
 * No database query is made, use HashIdExists for that.
 */
class HashId implements ValidationRule
{
    /**
     * @param  class-string<Model>|Model|string|null  $model  model class, model or key like "users"
     */
    public function __construct(protected Model|string|null $model = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (null === $this->decode($value)) {
            $fail('validation.exists')->translate();
        }
    }

    protected function decode(mixed $value): ?int
    {
        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        return $this->hasher()->decodeId($value, false);
    }

    protected function hasher(): IdHasherManager
    {
        return null === $this->model ? IdHasher::getFacadeRoot() : IdHasher::for($this->model);
    }
}
