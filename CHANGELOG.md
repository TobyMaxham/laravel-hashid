# Changelog

All notable changes to `tobymaxham/laravel-hashid` will be documented in this file

## 2.0.0 - 2026-11-09

Major release with breaking changes. Please read the [upgrade guide](UPGRADING.md).

### Breaking Changes

- **All hashes change:** every model gets its own hashes (`per_model`, enabled by default). Use `HASH_ACCEPT_LEGACY=true` to accept the old hashes during the upgrade, or `HASH_PER_MODEL=false` to keep them
- Requires PHP 8.3+, Laravel 12 or 13 and `hashids/hashids` 5 (dropped PHP 8.2, Laravel 10 and 11; the hashes of `hashids/hashids` 5 are identical to 4)
- Route binding: `Hasher` overrides `resolveRouteBindingQuery()` instead of `resolveRouteBinding()`; an invalid hash throws `ModelNotFoundException` instead of returning `null`
- `Hasher` can no longer be combined with Laravel's `HasUuids` / `HasUlids` traits
- `Hasher` adds new methods and scopes, which can collide with methods of your models
- Default exception is `InvalidHashIdException` (extends the now deprecated `WrongIdException`) with the message "The given hash id is invalid."
- `safeHashID()` returns a `string` instead of a `Stringable`
- `decodeId()` only accepts hashes of exactly one integer
- The config is validated (`length`, `alphabet`, `exception`); a prefix may only contain letters, digits and `-` `.` `_` `~` `:`
- `IdHasherManager` is a singleton, creates its hasher itself and has a new constructor and typed methods
- The config file is published with the tag `hashid-config`
- All classes use `declare(strict_types=1)`

### Added

- Hashes per model with a model key (`hashIdKey()`, `HASH_ID_KEY`, `$hashIdKey`, default: table name)
- Prefix per model (`hashIdPrefix()`, `HASH_ID_PREFIX`, `$hashIdPrefix`)
- `IdHasher::for($model)` for the hasher of a model or a custom key
- `prefix_mode` (`HASH_PREFIX_MODE`): `strict` (default) or `optional` to accept hashes with and without prefix (#6)
- Empty prefix (`HASH_PREFIX=`) is officially supported
- Query methods `findByHashId()`, `findByHashIdOrFail()`, `findManyByHashIds()`, `decodeHashId()`, `decodeHashIds()`
- Query scopes `whereHashId()`, `orWhereHashId()`, `whereHashIdIn()`, `whereHashIdNot()`, `whereHashIdNotIn()`
- Validation rules `HashId` and `HashIdExists`
- Request macros `$request->hashId()` and `$request->hashIds()`
- Trait `SerializesHashIds` for hashes in `toArray()` / JSON, incl. foreign keys
- Interface `HasHashId` (extends `Hashing`)
- Artisan commands `hashid:encode` and `hashid:decode`
- Salt rotation with `previous_salts` (`HASH_PREVIOUS_SALTS`)
- `accept_legacy_hashes` (`HASH_ACCEPT_LEGACY`) for the upgrade from 1.x
- Warning in the log (production) and in `php artisan about` when the default salt is used
- `IdHasher::encodeIdWithoutPrefix()`, `prefix()`, `prefixMode()`, `fallbackSalts()`, `usesDefaultSalt()`
- Test suite (Pest, 100 % coverage), PHPStan level 8 and GitHub Actions workflow

### Fixed

- Route bindings with a custom field like `{product:slug}` no longer decode the value as a hash
- Scoped child route bindings like `products/{product}/reviews/{review}` decode the hash
- Routes with `->withTrashed()` find soft deleted models by their hash
- A missing GMP/BCMath extension no longer breaks the whole application on boot, it is reported on the first hash
- Hashes with several numbers or numbers larger than `PHP_INT_MAX` were accepted as id
- Wrong class names and namespaces in the readme

### Changed

- Hashers are cached per model
- Contact email changed to `git@maxham.net`

## 1.1 - 2025-04-12

- Added support for Laravel 12
- Added some git and editorconfig files

## 1.0 - 2025-02-21

- Cleaned up the code
- Added Laravel Facade
- Renamed some classes
- Added configuration of Exception to be thrown
- Added the `readme.md` file
- Added the `CHANGELOG.md` file
- Added the `LICENSE.md` file

## ..0.4 - 2024-04-03

- Initial release
- Added support for different Laravel versions

