<?php

namespace TobyMaxham\HashId;

use Hashids\Hashids;
use InvalidArgumentException;
use TobyMaxham\HashId\Exceptions\WrongIdException;

class IdHasherManager
{
    /** The hash must start with the prefix. */
    public const PREFIX_STRICT = 'strict';

    /** The hash is accepted with and without the prefix. */
    public const PREFIX_OPTIONAL = 'optional';

    protected string $prefix;

    protected string $prefixMode;

    private Hashids $hasher;

    public function __construct(?string $prefix = null, ?string $prefixMode = null)
    {
        // null and '' both mean "no prefix" (e.g. HASH_PREFIX= or HASH_PREFIX=null in .env)
        $this->prefix = (string) ($prefix ?? config('hashids.prefix'));
        $this->prefixMode = $prefixMode ?? config('hashids.prefix_mode') ?? self::PREFIX_STRICT;

        if (! in_array($this->prefixMode, [self::PREFIX_STRICT, self::PREFIX_OPTIONAL], true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid hashids.prefix_mode [%s]. Use "%s" or "%s".',
                $this->prefixMode,
                self::PREFIX_STRICT,
                self::PREFIX_OPTIONAL,
            ));
        }
    }

    public function encodeId(int|string $id): string
    {
        return $this->prefix.$this->encodeIdWithoutPrefix($id);
    }

    public function encodeIdWithoutPrefix(int|string $id): string
    {
        return $this->hasher->encode($id);
    }

    public function decodeId(mixed $hash, bool $throw = true): ?int
    {
        $hash = (string) $hash;

        $id = $this->decodeWithPrefix($hash);

        if (null === $id && $this->acceptsHashWithoutPrefix()) {
            $id = $this->decodeRaw($hash);
        }

        if (null !== $id) {
            return $id;
        }

        if ($throw && ! config('hashids.disable_exception', false)) {
            $class = config('hashids.exception', WrongIdException::class);

            throw new $class(config('hashids.exception_message', 'WrongIdException'));
        }

        return null;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function prefixMode(): string
    {
        return $this->prefixMode;
    }

    public function setHasher(Hashids $hasher): void
    {
        $this->hasher = $hasher;
    }

    protected function decodeWithPrefix(string $hash): ?int
    {
        if ('' === $this->prefix) {
            return $this->decodeRaw($hash);
        }

        if (! str_starts_with($hash, $this->prefix)) {
            return null;
        }

        return $this->decodeRaw(substr($hash, strlen($this->prefix)));
    }

    protected function acceptsHashWithoutPrefix(): bool
    {
        return '' !== $this->prefix && self::PREFIX_OPTIONAL === $this->prefixMode;
    }

    /**
     * Hashids validates the hash itself (it re-encodes the result and compares).
     * Only a hash of exactly one integer is a valid id.
     */
    protected function decodeRaw(string $hash): ?int
    {
        if ('' === $hash) {
            return null;
        }

        $result = $this->hasher->decode($hash);

        if (1 !== count($result) || ! is_int($result[0])) {
            return null;
        }

        return $result[0];
    }
}
