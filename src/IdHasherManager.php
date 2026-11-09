<?php

declare(strict_types=1);

namespace TobyMaxham\HashId;

use Throwable;
use Hashids\Hashids;
use RuntimeException;
use InvalidArgumentException;
use Illuminate\Database\Eloquent\Model;
use TobyMaxham\HashId\Exceptions\InvalidHashIdException;

/**
 * @phpstan-consistent-constructor
 */
class IdHasherManager
{
    /** The hash must start with the prefix. */
    public const PREFIX_STRICT = 'strict';

    /** The hash is accepted with and without the prefix. */
    public const PREFIX_OPTIONAL = 'optional';

    protected string $prefix;

    protected string $prefixMode;

    protected string $salt;

    private ?Hashids $hasher = null;

    /** @var list<string> salts that are only used to decode, e.g. after a salt rotation */
    protected array $fallbackSalts;

    /** @var list<Hashids>|null */
    private ?array $fallbackHashers = null;

    /** Default salt of the config file, must be changed in production. */
    public const DEFAULT_SALT = 'your-salt-string';

    private static bool $warnedAboutDefaultSalt = false;

    /** @var array<string, static> */
    protected array $scoped = [];

    /**
     * @param  list<string>|null  $fallbackSalts  defaults to hashids.previous_salts
     */
    public function __construct(?string $prefix = null, ?string $prefixMode = null, ?string $salt = null, ?array $fallbackSalts = null)
    {
        // null and '' both mean "no prefix" (e.g. HASH_PREFIX= or HASH_PREFIX=null in .env)
        $this->prefix = (string) ($prefix ?? config('hashids.prefix'));
        $this->prefixMode = $prefixMode ?? config('hashids.prefix_mode') ?? self::PREFIX_STRICT;
        $this->salt = (string) ($salt ?? config('hashids.salt'));
        $this->fallbackSalts = array_values(array_unique(array_diff(
            array_map('strval', $fallbackSalts ?? static::previousSalts()),
            [$this->salt],
        )));

        // Only characters that work everywhere in URLs without encoding (RFC 3986 "unreserved") plus ":".
        if (1 !== preg_match('/^[A-Za-z0-9._~:-]*\z/', $this->prefix)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid hashids.prefix [%s]. Only letters (a-z, A-Z), digits and "-", ".", "_", "~", ":" are allowed, other characters are not URL safe.',
                $this->prefix,
            ));
        }

        if (! in_array($this->prefixMode, [self::PREFIX_STRICT, self::PREFIX_OPTIONAL], true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid hashids.prefix_mode [%s]. Use "%s" or "%s".',
                $this->prefixMode,
                self::PREFIX_STRICT,
                self::PREFIX_OPTIONAL,
            ));
        }
    }

    /**
     * Get the hasher for a model (instance or class name) or for a custom key like "users".
     *
     * With hashids.per_model enabled, the key is added to the salt, so the same id
     * creates a different hash for every model. Instances are cached per model.
     */
    public function for(Model|string $model): static
    {
        $cacheKey = $model instanceof Model ? $model::class : $model;

        return $this->scoped[$cacheKey] ??= $this->makeScoped($model);
    }

    public function encodeId(int|string $id): string
    {
        return $this->prefix.$this->encodeIdWithoutPrefix($id);
    }

    public function encodeIdWithoutPrefix(int|string $id): string
    {
        return $this->hasher()->encode($id);
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
            throw $this->makeException();
        }

        return null;
    }

    protected function makeException(): Throwable
    {
        $class = config('hashids.exception', InvalidHashIdException::class);
        $message = (string) config('hashids.exception_message', 'The given hash id is invalid.');

        if (! is_string($class) || ! is_a($class, Throwable::class, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid hashids.exception [%s]. It must be the class name of an exception.',
                is_string($class) ? $class : get_debug_type($class),
            ));
        }

        return new $class($message);
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

    /**
     * @return list<string>
     */
    public function fallbackSalts(): array
    {
        return $this->fallbackSalts;
    }

    public function usesDefaultSalt(): bool
    {
        return '' === $this->salt || self::DEFAULT_SALT === $this->salt;
    }

    protected function hasher(): Hashids
    {
        return $this->hasher ??= $this->makeHashids($this->salt);
    }

    /**
     * @return list<Hashids>
     */
    protected function fallbackHashers(): array
    {
        return $this->fallbackHashers ??= array_map(fn (string $salt) => $this->makeHashids($salt), $this->fallbackSalts);
    }

    protected function makeHashids(string $salt): Hashids
    {
        if (! $this->hasMathExtension()) {
            throw new RuntimeException(
                'Missing required PHP extension: either GMP or BCMath must be installed for hashids/hashids to work.'
            );
        }

        $length = config('hashids.length', 0);

        if (! is_numeric($length) || (int) $length < 0 || (int) $length != $length) {
            throw new InvalidArgumentException(sprintf('Invalid hashids.length [%s]. It must be an integer of 0 or more.', $length));
        }

        $alphabet = (string) config('hashids.alphabet');

        try {
            $hashids = new Hashids($salt, (int) $length, $alphabet);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Invalid hashids.alphabet [%s]: %s', $alphabet, $e->getMessage()), 0, $e);
        }

        $this->warnAboutDefaultSalt();

        return $hashids;
    }

    /**
     * Logs once per process in production, so the default salt is noticed.
     */
    protected function warnAboutDefaultSalt(): void
    {
        if (self::$warnedAboutDefaultSalt || ! $this->usesDefaultSalt() || ! app()->isProduction()) {
            return;
        }

        self::$warnedAboutDefaultSalt = true;

        logger()->warning('laravel-hashid uses the default salt. Set HASH_SALT in your .env, otherwise your hashes can be decoded by anyone.');
    }

    /**
     * @internal for tests
     */
    public static function resetDefaultSaltWarning(): void
    {
        self::$warnedAboutDefaultSalt = false;
    }

    /**
     * @return list<string>
     */
    protected static function previousSalts(): array
    {
        $salts = config('hashids.previous_salts', []);

        if (is_string($salts)) {
            $salts = explode(',', $salts);
        }

        return array_values(array_filter(array_map('trim', (array) $salts), fn (string $salt) => '' !== $salt));
    }

    protected function hasMathExtension(): bool
    {
        return extension_loaded('gmp') || extension_loaded('bcmath');
    }

    protected function makeScoped(Model|string $model): static
    {
        if (is_string($model) && is_subclass_of($model, Model::class)) {
            $model = new $model;
        }

        $key = $model;
        $prefix = null;

        if ($model instanceof Model) {
            $key = method_exists($model, 'hashIdKey') ? $model->hashIdKey() : $model->getTable();
            $prefix = method_exists($model, 'hashIdPrefix') ? $model->hashIdPrefix() : null;
        }

        if (! config('hashids.per_model', true)) {
            return new static($prefix ?? $this->prefix, $this->prefixMode, $this->salt, $this->fallbackSalts);
        }

        // previous salts with the model key, so a salt rotation also works per model
        $fallbacks = array_map(fn (string $salt) => $salt.'|'.$key, $this->fallbackSalts);

        // hashes of 1.x (or per_model disabled): global salt without the model key
        if (config('hashids.accept_legacy_hashes', false)) {
            $fallbacks = [...$fallbacks, $this->salt, ...$this->fallbackSalts];
        }

        return new static($prefix ?? $this->prefix, $this->prefixMode, $this->salt.'|'.$key, $fallbacks);
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

        $id = $this->decodeWith($this->hasher(), $hash);

        if (null !== $id || [] === $this->fallbackSalts) {
            return $id;
        }

        foreach ($this->fallbackHashers() as $hasher) {
            if (null !== $id = $this->decodeWith($hasher, $hash)) {
                return $id;
            }
        }

        return null;
    }

    protected function decodeWith(Hashids $hasher, string $hash): ?int
    {
        // The PHPDoc of Hashids says list<int>, but numbers larger than PHP_INT_MAX
        // are returned as strings (see phpstan.neon.dist: treatPhpDocTypesAsCertain).
        $result = $hasher->decode($hash);

        if (1 !== count($result) || ! is_int($result[0])) {
            return null;
        }

        return $result[0];
    }
}
