# From 1.x to 2.0

Version 2.0 contains breaking changes. Most apps only need the checklist, the sections below explain every change.

## Checklist

1. Update to PHP 8.3+ and Laravel 12 or 13.
2. Run `composer require tobymaxham/laravel-hashid:^2.0`.
3. **Decide how to handle the old hashes** (see [All hashes change](#all-hashes-change)). Most apps should set `HASH_ACCEPT_LEGACY=true` for the transition.
4. Make sure `HASH_SALT` is set (not `your-salt-string`). Check it with `php artisan about`.
5. If you published `config/hashids.php`: add the [new keys](#config) or publish it again.
6. Search your code for `resolveRouteBinding(`, `safeHashID()` and `WrongIdException` (see below).
7. Remove `Hasher` from models with `HasUuids` / `HasUlids`.

## Requirements

| | 1.x | 2.0 |
|---|---|---|
| PHP | 8.2+ | **8.3+** |
| Laravel | 10 – 13 | **12, 13** |
| `hashids/hashids` | 4.x | **5.x** |

The update of `hashids/hashids` does not change any hashes. If you use `hashids/hashids` directly in your app, check its [releases](https://github.com/vinkla/hashids/releases).

## All hashes change

**This is the most important change.** In 1.x all models shared the same hashes, so user `400` and post `400` had the same hash.
In 2.0 every model gets its own hashes (the model key is added to the salt). **All existing hashes change**, e.g. links in emails, bookmarks or ids stored in other systems.

Choose one option:

**a) Accept the old hashes during the upgrade (recommended).** New hashes are created per model, old links keep working:

```dotenv
HASH_ACCEPT_LEGACY=true
```

Remove it later, when the old links are no longer needed. This works as long as the prefix of the model is unchanged.

**b) Keep the 1.x hashes forever:**

```dotenv
HASH_PER_MODEL=false
```

**c) Switch hard:** do nothing, old hashes return a 404.

With per-model hashes the model key is the table name. Set a fixed key with `HASH_ID_KEY`, `$hashIdKey` or `hashIdKey()` if the table name may change later (see readme).

`IdHasher::encodeId()` / `decodeId()` still use the global salt. Use `IdHasher::for(User::class)` for the hashes of a model, e.g. in custom code that decoded route keys with the facade:

```php
// before
$id = IdHasher::decodeId($hash);

// after
$id = IdHasher::for(Product::class)->decodeId($hash);
// or
$product = Product::findByHashIdOrFail($hash);
```

## Config

If you published `config/hashids.php`, add these keys (or run `php artisan vendor:publish --tag=hashid-config --force` and set your values again):

```php
'prefix_mode'          => env('HASH_PREFIX_MODE', 'strict'),
'previous_salts'       => env('HASH_PREVIOUS_SALTS', ''),
'per_model'            => env('HASH_PER_MODEL', true),
'accept_legacy_hashes' => env('HASH_ACCEPT_LEGACY', false),
'exception'            => \TobyMaxham\HashId\Exceptions\InvalidHashIdException::class,
'exception_message'    => 'The given hash id is invalid.',
```

Missing keys fall back to the package defaults, so `per_model` is enabled even if the key is missing.

The config is validated now. These values throw an `InvalidArgumentException` (they did not work correctly before anyway):

- a prefix with `/`, `?`, `#`, `%` or spaces (breaks URLs)
- a negative or non-integer `length`
- an invalid `alphabet` (with the config key in the message)
- an `exception` that is not an exception class

## Route binding

### `resolveRouteBindingQuery()` instead of `resolveRouteBinding()`

The `Hasher` trait now decodes the hash in `resolveRouteBindingQuery()`.
Laravel uses this method for normal, scoped (child) and `withTrashed()` route bindings, so all of them work with hashes now.

- **If you override `resolveRouteBinding()`** and call `parent::resolveRouteBinding()`, nothing changes.
- **If you override `resolveRouteBindingQuery()`**, call `parent::resolveRouteBindingQuery()`, otherwise the hash is not decoded.

### Invalid hashes throw `ModelNotFoundException`

```php
// before
(new Product)->resolveRouteBinding('h:invalid'); // null

// after
(new Product)->resolveRouteBinding('h:invalid'); // throws ModelNotFoundException
```

Routes still return a 404, so most apps do not need to change anything.
Only code that calls `resolveRouteBinding()` directly and checks for `null` must catch the exception. This is the same behavior as Laravel's `HasUuids` trait.
A valid hash of a missing record still returns `null`.

### Custom route fields are no longer decoded

Routes like `{product:slug}` now look up the plain value. Before, the value was decoded as a hash and the route always returned a 404.

## UUID / ULID keys

`Hasher` can no longer be used together with `HasUuids` or `HasUlids`, because both define `resolveRouteBindingQuery()`.
Hashing only works for integer keys anyway (UUID keys created an empty hash like `h:`), so remove `Hasher` from these models.

## New methods in the `Hasher` trait

- `hashIdKey()`, `hashIdPrefix()`, `hashIdManager()`
- static `decodeHashId()`, `decodeHashIds()`, `findByHashId()`, `findByHashIdOrFail()`, `findManyByHashIds()`
- scopes `whereHashId()`, `orWhereHashId()`, `whereHashIdIn()`, `whereHashIdNot()`, `whereHashIdNotIn()`

If your model already has methods with these names, your methods win over the trait. Rename them, if they do something else.

The package also registers the request macros `hashId()` and `hashIds()`. Rename your own macros with these names.

## `safeHashID()` returns a `string`

Before it returned a `Stringable` (`Str::of()`). If you called `Stringable` methods on it, wrap it yourself:

```php
Str::of($model->safeHashID())->upper();
```

## Exceptions

The default exception is now `InvalidHashIdException` with the message "The given hash id is invalid." (before: `WrongIdException` / "WrongIdException").
`InvalidHashIdException` extends `WrongIdException`, so `catch (WrongIdException $e)` still works.
`WrongIdException` is deprecated and will be removed in 3.0.

## Decoding

`decodeId()` only accepts hashes that contain exactly one integer.
Hashes created with several numbers (`Hashids::encode(1, 2)`) or numbers larger than `PHP_INT_MAX` are invalid.
Hashes created by this package are not affected.

## `IdHasherManager`

Only relevant if you create or extend the manager yourself.

- It is registered as singleton and creates its `Hashids` instance itself. `setHasher()` still exists.
- New constructor: `new IdHasherManager(?string $prefix = null, ?string $prefixMode = null, ?string $salt = null, ?array $fallbackSalts = null)`.
- All methods have parameter and return types.
- A missing GMP/BCMath extension throws on the first hash instead of on boot.

## Interface `HasHashId`

New interface for models with the `Hasher` trait. It extends `Hashing`, so code that checks for `Hashing` keeps working. We recommend to switch:

```php
class Product extends Model implements HasHashId // before: implements Hashing
```

`ModelWithHasher` implements it already.

# From 0.4 to 1.0

Please be aware that the package has been completely rewritten.
The API has changed and the package is now using a different class names.

If you are upgrading from 0.4 to 1.0, please make sure to implement the new classes and methods.
When only used the `HashId` trait, nothing has to be changed. This will be moved in the next version.
