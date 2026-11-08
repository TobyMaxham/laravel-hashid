# From 1.x to 2.0

Version 2.0 contains breaking changes. Please check every point below.

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

## UUID / ULID keys

`Hasher` can no longer be used together with Laravel's `HasUuids` or `HasUlids` traits, because both define `resolveRouteBindingQuery()`.
Hashing only works for integer keys anyway (UUID keys created an empty hash like `h:`), so remove `Hasher` from these models.

# From 0.4 to 1.0

Please be aware that the package has been completely rewritten.
The API has changed and the package is now using a different class names.

If you are upgrading from 0.4 to 1.0, please make sure to implement the new classes and methods.
When only used the `HashId` trait, nothing has to be changed. This will be moved in the next version.
