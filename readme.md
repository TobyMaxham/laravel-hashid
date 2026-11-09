[![Laravel HashID][laravel-hashid-image]][laravel-hashid-edit-link]

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tobymaxham/laravel-hashid.svg?style=flat-square)](https://packagist.org/packages/tobymaxham/laravel-hashid)
[![Tests](https://img.shields.io/github/actions/workflow/status/TobyMaxham/laravel-hashid/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/TobyMaxham/laravel-hashid/actions/workflows/tests.yml)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/tobymaxham/laravel-hashid.svg?style=flat-square)](https://packagist.org/packages/tobymaxham/laravel-hashid)
[![Support me on Patreon](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Fshieldsio-patreon.vercel.app%2Fapi%3Fusername%3DTobymaxham%26type%3Dpatrons&style=flat)](https://patreon.com/Tobymaxham)


This Laravel package ensures that IDs are not directly visible in URLs or other public areas.
Instead, they are encoded and, for example, `products/34` is converted to `products/h:J7dVgYxKPwyQejOMnL`.



## Requirements

- PHP 8.3 or higher
- Laravel 12 or 13
- PHP extension GMP or BCMath (GMP is faster)

Use version `1.x` for older PHP or Laravel versions.


## Installation

You can install the package via composer:

```sh
composer require tobymaxham/laravel-hashid
```

> This package relies on `hashids/hashids`, which requires either the GMP or BCMath extension. GMP is recommended for best performance and should be installed if possible.


## Configuration

Publish the configuration file with:

```bash
php artisan vendor:publish --tag=hashid-config
```

For example, the configuration file `config/hashids.php` looks like this:

```php
return [
    'prefix'            => env('HASH_PREFIX', 'h:'),
    'prefix_mode'       => env('HASH_PREFIX_MODE', 'strict'),
    'salt'              => env('HASH_SALT', 'your-salt-string'),
    'previous_salts'    => env('HASH_PREVIOUS_SALTS', ''),
    'per_model'         => env('HASH_PER_MODEL', true),
    'accept_legacy_hashes' => env('HASH_ACCEPT_LEGACY', false),
    'length'            => env('HASH_LENGTH', 18),
    'alphabet'          => env('HASH_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
    'exception'         => \TobyMaxham\HashId\Exceptions\InvalidHashIdException::class,
    'exception_message' => 'The given hash id is invalid.',
    'disable_exception' => false,
];
```

### Prefix

Every hash starts with the prefix (default `h:`), e.g. `h:J7dVgYxKPwyQejOMnL`.

**No prefix:** set an empty value. This is fully supported, in routes and everywhere else.

```dotenv
HASH_PREFIX=
```

**Prefix mode:** `HASH_PREFIX_MODE` controls which hashes are accepted when decoding.

| Mode | Accepts | Use case |
|------|---------|----------|
| `strict` (default) | only `h:J7dVgYxKPwyQejOMnL` | one format everywhere |
| `optional` | `h:J7dVgYxKPwyQejOMnL` and `J7dVgYxKPwyQejOMnL` | prefix in public URLs, plain hash in APIs (`safeHashID()`) |

Hashes are always **generated** with the prefix. `safeHashID()` returns the hash without the prefix.

> The `:` in the default prefix is fine in URL paths. If a proxy or CDN encodes it, use a prefix like `h_` or `h-`.

## Usage

### Using the Facade

```php
use \TobyMaxham\HashId\Facades\IdHasher;

$hash = IdHasher::encodeId(34);
// result: h:J7dVgYxKPwyQejOMnL

$id = IdHasher::decodeId($hash);
// result: 34

IdHasher::encodeIdWithoutPrefix(34);
// result: J7dVgYxKPwyQejOMnL

IdHasher::decodeId('h:invalid', false);
// result: null (no exception)
```

### Using Dependency Injection

If you prefer to use dependency injection:

```php
use TobyMaxham\HashId\IdHasherManager;

public function show(IdHasherManager $idHasher, $hash)
{
    $id = $idHasher->decodeId($hash);

    return Product::findOrFail($id);
}
```

### Automatically Decode Hash IDs

You can use the trait `Hasher` in your models to automatically decode hash IDs:

```php
use TobyMaxham\HashId\Hasher;
use TobyMaxham\HashId\Interfaces\HasHashId;

class Product extends Model implements HasHashId
{
    use Hasher;
}
```


Now you can use them in routes:

```php
// web.php
Route::get('products/{product}', 'ProductController@show');


// ProductController.php
class ProductController extends Controller
{
    public function show(Product $product)
    {
        return $product;
    }
}
```

Implementing `HasHashId` is optional, but lets other code (e.g. API resources) detect hashed models. It extends the older `Hashing` interface.
You can also extend `TobyMaxham\HashId\ModelWithHasher` instead.

Invalid hashes return a 404. Custom fields like `{product:slug}`, scoped bindings like `products/{product}/reviews/{review}` and `->withTrashed()` routes are supported.

The model also gets these methods:

```php
$product->hashID();     // h:J7dVgYxKPwyQejOMnL
$product->safeHashID(); // J7dVgYxKPwyQejOMnL
```

> Hashing only works for integer keys. Do not combine `Hasher` with `HasUuids` or `HasUlids`.

### Queries

Find models by hash:

```php
Product::findByHashId($hash);                 // ?Product
Product::findByHashIdOrFail($hash);           // Product or ModelNotFoundException (404)
Product::findManyByHashIds([$hash1, $hash2]); // Collection, invalid hashes are skipped
Product::findByHashId($hash, ['id', 'name']); // only these columns

Product::decodeHashId($hash);                 // ?int, no query
Product::decodeHashIds([$hash1, $hash2]);     // list of ints, invalid hashes are skipped
```

Or use the scopes in any query, on relations and inside `whereHas()`:

```php
Product::whereHashId($hash)->first();
Product::whereHashId($hash1)->orWhereHashId($hash2)->get();
Product::whereHashIdIn([$hash1, $hash2])->get();
Product::whereHashIdNot($hash)->get();
Product::whereHashIdNotIn([$hash1, $hash2])->get();

$product->reviews()->whereHashId($reviewHash)->first();

Review::whereHas('product', fn ($query) => $query->whereHashId($productHash))->get();
```

- The query is a normal `where id = ?` / `where id in (...)` on the qualified key (`products.id`), so joins work.
- Invalid hashes or hashes of another model never throw: `whereHashId()` matches no rows, `findByHashId()` returns `null` without a query.
- `Model::find()` and `where('id', ...)` are not changed and still expect the real id.

### Validation

```php
use TobyMaxham\HashId\Rules\HashId;
use TobyMaxham\HashId\Rules\HashIdExists;

$request->validate([
    'product_id'  => ['required', new HashIdExists(Product::class)], // valid hash and record exists
    'category_id' => ['nullable', new HashId(Category::class)],      // valid hash, no query
    'active_id'   => [(new HashIdExists(Product::class))->where(fn ($query) => $query->where('active', true))],
]);
```

Both rules use Laravel's `validation.exists` message ("The selected :attribute is invalid.").

### Request

Get the id from a hash in the request (`null` for missing or invalid values):

```php
$id  = $request->hashId('product_id', Product::class); // ?int
$ids = $request->hashIds('product_ids', Product::class); // list<int>, invalid hashes are skipped
```

### JSON / toArray()

By default `toArray()` and JSON contain the real id. Add `SerializesHashIds` to output hashes instead:

```php
use TobyMaxham\HashId\Hasher;
use TobyMaxham\HashId\SerializesHashIds;

class Review extends Model
{
    use Hasher;
    use SerializesHashIds;

    // optional: foreign keys to hash with the salt of their model
    protected array $hashIdForeignKeys = ['product_id' => Product::class];
}

$review->toArray(); // ['id' => 'h:…', 'product_id' => 'h:…', ...]
$review->id;        // still the real id, relations keep working
```

### Artisan

```bash
php artisan hashid:encode 34 400 --model="App\Models\Product"
php artisan hashid:decode h:J7dVgYxKPwyQejOMnL --model=products
```

Without `--model` the global salt is used.

### Exceptions

`IdHasher::decodeId()` throws `InvalidHashIdException` for invalid hashes (configurable with `exception` and `exception_message`, or disabled with `disable_exception`).
Pass `false` as second argument to get `null` instead: `IdHasher::decodeId($hash, false)`.

### Hashes per model

Every model gets its own hashes, so user `400` and post `400` have different hashes.
A hash of one model can not be used for another model (the route returns a 404).

The model key is added to the salt. By default it is the table name.
Set your own key, so the hashes stay the same when you rename the table:

```php
class User extends Model
{
    use Hasher;

    // any of these:
    public const HASH_ID_KEY = 'user';
    protected string $hashIdKey = 'user';

    public function hashIdKey(): string
    {
        return 'user';
    }
}
```

> Changing the key changes all hashes of the model.

**Prefix per model:** works the same way with `HASH_ID_PREFIX`, `$hashIdPrefix` or `hashIdPrefix()`.
`null` uses the global prefix, `''` disables the prefix for this model.

```php
class User extends Model
{
    use Hasher;

    public const HASH_ID_PREFIX = 'usr_'; // usr_J7dVgYxKPwyQejOMnL
}
```

**Outside of models**, get the hasher of a model with `IdHasher::for()`:

```php
IdHasher::for(User::class)->encodeId(400);
IdHasher::for($user)->decodeId($hash);
IdHasher::for('users')->encodeId(400); // custom key
```

`IdHasher::encodeId()` without `for()` uses the global salt.

To use the same hashes for all models (like 1.x), disable it:

```dotenv
HASH_PER_MODEL=false
```

## Security

- **Set your own salt** with `HASH_SALT` and keep it secret. With the default salt (`your-salt-string`) anyone can decode your hashes.
  In production the package logs a warning once per process, and `php artisan about` shows the salt status.
- **Hashes are not encryption.** They only hide the real ids and prevent counting through them (`/users/1`, `/users/2`, …).
  They are **no replacement for authorization**: always check access with policies or gates.
- **Rotate a leaked salt** without breaking old links: set the new salt and add the old one to `HASH_PREVIOUS_SALTS`.
  Old hashes are still accepted, new hashes are created with the new salt.

  ```dotenv
  HASH_SALT=new-secret
  HASH_PREVIOUS_SALTS=old-secret
  ```

- The config is checked when the first hash is created: invalid `alphabet`, `length`, `exception` or a prefix with `/`, `?`, `#`, `%` or spaces throw an `InvalidArgumentException` with the config key in the message.
- **Laravel Octane:** supported. The hasher only keeps data from the config, no request state.


## Testing

```bash
composer test
composer test-coverage
composer analyse
```


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.


## Security Vulnerabilities

If you've found a bug regarding security please mail [git@maxham.net](mailto:git@maxham.net) instead of using the issue tracker.


## Support me

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/Z8Z4NZKU)<br>
[![Support me on Patreon](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Fshieldsio-patreon.vercel.app%2Fapi%3Fusername%3DTobymaxham%26type%3Dpatrons&style=flat)](https://patreon.com/Tobymaxham)


## Credits

- [TobyMaxham](https://github.com/TobyMaxham)
- [All Contributors](../../contributors)


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


[laravel-hashid-image]: https://socialify.git.ci/TobyMaxham/laravel-hashid/image?description=1&font=Raleway&issues=1&language=1&owner=1&pattern=Charlie%20Brown&pulls=1&stargazers=1&theme=Light
[laravel-hashid-edit-link]: https://socialify.git.ci/TobyMaxham/laravel-hashid?description=1&font=Raleway&issues=1&language=1&owner=1&pattern=Charlie%20Brown&pulls=1&stargazers=1&theme=Light
