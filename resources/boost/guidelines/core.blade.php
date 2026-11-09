## Laravel HashID (tobymaxham/laravel-hashid)

This package hides database ids behind hashes like `h:J7dVgYxKPwyQejOMnL` in URLs, queries, validation and JSON.

- Add `use \TobyMaxham\HashId\Hasher;` and `implements \TobyMaxham\HashId\Interfaces\HasHashId` to models with public ids. Route model binding then works with hashes automatically.
- Only use it on models with integer keys. Never combine it with `HasUuids` or `HasUlids`.
- Every model has its own hashes. Never decode a model hash with `IdHasher::decodeId()`, use the model methods instead:
  - `Product::findByHashId($hash)`, `Product::findByHashIdOrFail($hash)`, `Product::findManyByHashIds($hashes)`
  - scopes `whereHashId()`, `orWhereHashId()`, `whereHashIdIn()`, `whereHashIdNot()`, `whereHashIdNotIn()`
  - `Product::decodeHashId($hash)` or `IdHasher::for(Product::class)->decodeId($hash)` if only the id is needed
- Get the hash of a model with `$product->hashID()` (with prefix) or `$product->safeHashID()` (without prefix).
- Validate hashes with the rules `new \TobyMaxham\HashId\Rules\HashId(Product::class)` or `new \TobyMaxham\HashId\Rules\HashIdExists(Product::class)`, and read them with `$request->hashId('product_id', Product::class)` / `$request->hashIds('product_ids', Product::class)`.
- Use the trait `\TobyMaxham\HashId\SerializesHashIds` to output hashes instead of ids in `toArray()` / JSON.
- Never change `HASH_SALT`, `HASH_ALPHABET`, `HASH_LENGTH`, the prefix or the model key (`HASH_ID_KEY`, default: table name) without asking. It changes all existing hashes. To rotate the salt, move the old one to `HASH_PREVIOUS_SALTS`.
- In tests, never hard code hashes. Use `$model->hashID()`.
- Debug with `php artisan hashid:encode` / `php artisan hashid:decode`.
- For upgrades between major versions use the skill `laravel-hashid-upgrade`.
