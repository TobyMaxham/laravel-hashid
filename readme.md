[![Laravel HashID][laravel-hashid-image]][laravel-hashid-edit-link]

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tobymaxham/laravel-hashid.svg?style=flat-square)](https://packagist.org/packages/tobymaxham/laravel-hashid)
[![Tests](https://img.shields.io/github/actions/workflow/status/TobyMaxham/laravel-hashid/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/TobyMaxham/laravel-hashid/actions/workflows/tests.yml)
[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Total Downloads](https://img.shields.io/packagist/dt/tobymaxham/laravel-hashid.svg?style=flat-square)](https://packagist.org/packages/tobymaxham/laravel-hashid)
[![Support me on Patreon](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Fshieldsio-patreon.vercel.app%2Fapi%3Fusername%3DTobymaxham%26type%3Dpatrons&style=flat)](https://patreon.com/Tobymaxham)


This Laravel package ensures that IDs are not directly visible in URLs or other public areas.
Instead, they are encoded and, for example, `products/34` is converted to `products/h:J7dVgYxKPwyQejOMnL`.



## Installation

You can install the package via composer:

```sh
composer require tobymaxham/laravel-hashid
```

> This package relies on `hashids/hashids`, which requires either the GMP or BCMath extension. GMP is recommended for best performance and should be installed if possible.


## Configuration

Publish the configuration file with:

```bash
php artisan vendor:publish --provider="TobyMaxham\HashId\HashIdServiceProvider"
```

For example, the configuration file `config/hashids.php` looks like this:

```php
return [
    'prefix'            => env('HASH_PREFIX', 'h:'),
    'prefix_mode'       => env('HASH_PREFIX_MODE', 'strict'),
    'salt'              => env('HASH_SALT', 'your-salt-string'),
    'length'            => env('HASH_LENGTH', 18),
    'alphabet'          => env('HASH_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
    'exception'         => \TobyMaxham\HashId\Exceptions\WrongIdException::class,
    'exception_message' => 'WrongIdException',
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

class Product extends Model
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

Invalid hashes return a 404. Custom fields like `{product:slug}`, scoped bindings like `products/{product}/reviews/{review}` and `->withTrashed()` routes are supported.

The model also gets these methods:

```php
$product->hashID();     // h:J7dVgYxKPwyQejOMnL
$product->safeHashID(); // J7dVgYxKPwyQejOMnL
```

> Hashing only works for integer keys. Do not combine `Hasher` with `HasUuids` or `HasUlids`.

## Security

- Make sure your **salt value remains secret** and is not published in your repository.
- The encoded IDs are not encrypted, just encoded. Therefore, they are not secure and should not be used for security purposes.


## Testing

```bash
composer test
composer test-coverage
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
