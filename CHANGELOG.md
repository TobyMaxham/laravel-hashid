# Changelog

All notable changes to `tobymaxham/laravel-hashid` will be documented in this file

## 2.0 - Unreleased

### Breaking Changes

_Every breaking change is also described in [UPGRADING.md](UPGRADING.md)._

- `Hasher` now overrides `resolveRouteBindingQuery()` instead of `resolveRouteBinding()`
- An invalid hash in `resolveRouteBinding()` now throws `ModelNotFoundException` instead of returning `null` (no database query is made)
- `Hasher` can no longer be combined with Laravel's `HasUuids` / `HasUlids` traits
- `safeHashID()` returns a `string` instead of a `Stringable`
- `IdHasherManager::decodeId()` only accepts hashes of exactly one integer (hashes of several numbers or numbers larger than `PHP_INT_MAX` are invalid)
- `IdHasherManager` has typed parameters and return types

### Added

- Test suite with Pest and Orchestra Testbench
- GitHub Actions workflow for tests and code style
- New config option `prefix_mode` (`HASH_PREFIX_MODE`): `strict` (default) or `optional` to accept hashes with and without prefix (#6)
- `IdHasher::encodeIdWithoutPrefix()`, `IdHasher::prefix()`, `IdHasher::prefixMode()`
- Empty prefix (`HASH_PREFIX=` or `HASH_PREFIX=null`) is now officially supported and documented

### Fixed

- Route bindings with a custom field (e.g. `{product:slug}`) no longer try to decode the value as a hash
- Scoped child route bindings (e.g. `products/{product}/reviews/{review}`) now decode the hash
- Routes with `->withTrashed()` now find soft deleted models by their hash

### Changed

- Fixed class names and namespaces in the readme
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

