# From 1.x to 2.0

Version 2.0 contains breaking changes. Please check every point below.

## All hashes change (hashes per model)

**This is the most important change.** In 1.x all models shared the same hashes, so user `400` and post `400` had the same hash.
In 2.0 every model gets its own hashes. **All existing hashes change**, e.g. links in emails, bookmarks or ids stored in other systems.

**To keep the 1.x hashes**, disable it:

```dotenv
HASH_PER_MODEL=false
```

or in your published `config/hashids.php`:

```php
'per_model' => env('HASH_PER_MODEL', false),
```

If you enable it, the model key is added to the salt. By default this is the table name.
Set a fixed key with `HASH_ID_KEY`, `$hashIdKey` or `hashIdKey()` if the table name may change later (see readme).

**Methods added to the `Hasher` trait:**

- `hashIdKey()`, `hashIdPrefix()`, `hashIdManager()`
- static `decodeHashId()`, `decodeHashIds()`, `findByHashId()`, `findByHashIdOrFail()`, `findManyByHashIds()`
- scopes `scopeWhereHashId()`, `scopeOrWhereHashId()`, `scopeWhereHashIdIn()`, `scopeWhereHashIdNot()`, `scopeWhereHashIdNotIn()`

If your model already has methods with these names, rename them or the trait methods will be overwritten by yours.

`IdHasher::encodeId()` / `decodeId()` still use the global salt. Use `IdHasher::for(User::class)` for the hashes of a model.

## Service provider

- `IdHasherManager` is a singleton now. It creates its `Hashids` instance itself; `setHasher()` still exists.
- New constructor: `new IdHasherManager(?string $prefix = null, ?string $prefixMode = null, ?string $salt = null)`.
- A missing GMP/BCMath extension throws on the first hash instead of on boot.
- Publish the config with `php artisan vendor:publish --tag=hashid-config` and add the new keys `prefix_mode` and `per_model` to your published file.

## Route binding

### `resolveRouteBindingQuery()` instead of `resolveRouteBinding()`

The `Hasher` trait now decodes the hash in `resolveRouteBindingQuery()`.
Laravel uses this method for normal, scoped (child) and `withTrashed()` route bindings, so all of them work with hashes now.

**If you override `resolveRouteBinding()` in your model** and call `parent::resolveRouteBinding()`, nothing changes.
**If you override `resolveRouteBindingQuery()` in your model**, call `parent::resolveRouteBindingQuery()`, otherwise the hash is not decoded.

### Invalid hashes throw `ModelNotFoundException`

Before:

```php
(new Product)->resolveRouteBinding('h:invalid'); // null
```

After:

```php
(new Product)->resolveRouteBinding('h:invalid'); // throws ModelNotFoundException
```

Routes still return a 404, so most applications do not need to change anything.
Only code that calls `resolveRouteBinding()` directly and checks for `null` must catch the exception instead.
This is the same behavior as Laravel's `HasUuids` trait. A valid hash of a missing record still returns `null`.

### Custom route fields are no longer decoded

Routes like `{product:slug}` now look up the plain value. Before, the value was decoded as a hash and the route always returned a 404.

## Prefix

### New option `prefix_mode`

Add it to your published `config/hashids.php`:

```php
'prefix_mode' => env('HASH_PREFIX_MODE', 'strict'),
```

`strict` is the default and behaves like 1.x. Use `optional` to also accept hashes without the prefix, e.g. values from `safeHashID()` in routes.

### `safeHashID()` returns a `string`

Before it returned a `Stringable` (`Str::of()`). If you called `Stringable` methods on it, wrap it yourself:

```php
Str::of($model->safeHashID())->upper();
```

## Decoding

`decodeId()` now only accepts hashes that contain exactly one integer.
Hashes created with several numbers (`Hashids::encode(1, 2)`) or numbers larger than `PHP_INT_MAX` are invalid.
Hashes created by this package are not affected.

## UUID / ULID keys

`Hasher` can no longer be used together with Laravel's `HasUuids` or `HasUlids` traits, because both define `resolveRouteBindingQuery()`.
Hashing only works for integer keys anyway (UUID keys created an empty hash like `h:`), so remove `Hasher` from these models.

# From 0.4 to 1.0

Please be aware that the package has been completely rewritten.
The API has changed and the package is now using a different class names.

If you are upgrading from 0.4 to 1.0, please make sure to implement the new classes and methods.
When only used the `HashId` trait, nothing has to be changed. This will be moved in the next version.
