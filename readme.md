# Laravel HashID - Sicheres Kodieren von IDs

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tobymaxham/laravel-hashid.svg?style=flat-square)](https://packagist.org/packages/tobymaxham/laravel-hashid)
[![Total Downloads](https://img.shields.io/packagist/dt/tobymaxham/laravel-hashid.svg?style=flat-square)](https://packagist.org/packages/tobymaxham/laravel-hashid)
[![Support me on Patreon](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Fshieldsio-patreon.vercel.app%2Fapi%3Fusername%3DTobymaxham%26type%3Dpatrons&style=flat)](https://patreon.com/Tobymaxham)


This Laravel package ensures that IDs are not directly visible in URLs or other public areas.
Instead, they are encoded and, for example, `products/34` is converted to `products/h:J7dVgYxKPwyQejOMnL`.



## Installation

You can install the package via composer:

```sh
composer require tobymaxham/laravel-hashid
```


## Configuration

Publish the configuration file with:

```bash
php artisan vendor:publish --provider="TobyMaxham\HashID\IdHasherServiceProvider"
```

For example, the configuration file `config/hashids.php` looks like this:

```php
return [
    'prefix'            => env('HASH_PREFIX', 'h:'),
    'salt'              => env('HASH_SALT', 'your-salt-string'),
    'length'            => env('HASH_LENGTH', 18),
    'alphabet'          => env('HASH_ALPHABET', 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'),
    'exception'         => \TobyMaxham\HashId\Exceptions\WrongIdException::class,
    'exception_message' => 'WrongIdException',
    'disable_exception' => false,
];
```

## Usage

### Using the Facade

```php
use \TobyMaxham\HashId\Facades\IdHasher;

$hash = IdHasher::encodeId(34);
// result: h:J7dVgYxKPwyQejOMnL

$id = IdHasher::decodeId($hash);
// result: 34
```

### Using Dependency Injection

If you prefer to use dependency injection:

```php
use TobyMaxham\HashID\IdHasherManager;

public function show(IdHasherManager $idHasher, $hash)
{
    $id = $idHasher->decodeId($hash);

    return Product::findOrFail($id);
}
```

### Automatically Decode Hash IDs

You can use the trait `HashId` in your models to automatically decode hash IDs:

```php
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

## Security

- Make sure your **salt value remains secret** and is not published in your repository.
- The encoded IDs are not encrypted, just encoded. Therefore, they are not secure and should not be used for security purposes.


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.


## Security Vulnerabilities

If you've found a bug regarding security please mail git@maxham.de instead of using the issue tracker.


## Support me

[![ko-fi](https://ko-fi.com/img/githubbutton_sm.svg)](https://ko-fi.com/Z8Z4NZKU)<br>
[![Support me on Patreon](https://img.shields.io/endpoint.svg?url=https%3A%2F%2Fshieldsio-patreon.vercel.app%2Fapi%3Fusername%3DTobymaxham%26type%3Dpatrons&style=flat)](https://patreon.com/Tobymaxham)


## Credits

- [TobyMaxham](https://github.com/TobyMaxham)
- [All Contributors](../../contributors)


## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

