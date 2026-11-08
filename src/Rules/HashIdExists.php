<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Rules;

use Closure;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * The value is a valid hash of the model and the record exists.
 */
class HashIdExists extends HashId
{
    protected ?Closure $using = null;

    /**
     * @param  class-string<Model>  $model
     */
    public function __construct(string $model)
    {
        if (! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException(sprintf('[%s] is not an Eloquent model.', $model));
        }

        parent::__construct($model);
    }

    /**
     * Add constraints to the query, e.g. fn (Builder $query) => $query->where('active', true).
     *
     * @param  Closure(Builder): mixed  $callback
     */
    public function where(Closure $callback): static
    {
        $this->using = $callback;

        return $this;
    }

    #[\Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $id = $this->decode($value);

        if (null === $id || ! $this->exists($id)) {
            $fail('validation.exists')->translate();
        }
    }

    protected function exists(int $id): bool
    {
        /** @var Builder $query */
        $query = (new $this->model)->newQuery()->whereKey($id);

        if (null !== $this->using) {
            ($this->using)($query);
        }

        return $query->exists();
    }
}
