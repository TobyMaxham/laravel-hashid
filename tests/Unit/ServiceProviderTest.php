<?php

declare(strict_types=1);

use TobyMaxham\HashId\IdHasherManager;
use Illuminate\Support\ServiceProvider;
use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\HashIdServiceProvider;
use TobyMaxham\HashId\Exceptions\InvalidHashIdException;

it('merges the package config', function () {
    expect(config('hashids'))->toMatchArray([
        'prefix'            => 'h:',
        'prefix_mode'       => 'strict',
        'length'            => 18,
        'exception'         => InvalidHashIdException::class,
        'exception_message' => 'The given hash id is invalid.',
        'disable_exception' => false,
    ]);
});

it('registers the manager in the container', function () {
    expect(app(IdHasherManager::class))->toBeInstanceOf(IdHasherManager::class)
        ->and(app('idhasher'))->toBeInstanceOf(IdHasherManager::class);
});

it('builds the hasher from the config', function () {
    $this->setHashConfig(['salt' => 'a', 'length' => 10, 'prefix' => '']);
    $a = app(IdHasherManager::class)->encodeId(1);

    $this->setHashConfig(['salt' => 'b']);
    $b = app(IdHasherManager::class)->encodeId(1);

    expect(strlen($a))->toBe(10)
        ->and($a)->not->toBe($b);
});

it('uses a custom alphabet from the config', function () {
    $this->setHashConfig(['alphabet' => 'abcdefghijklmnop', 'prefix' => '']);

    expect(app(IdHasherManager::class)->encodeId(12345))->toMatch('/^[a-p]+$/');
});

it('resolves the facade', function () {
    $hash = IdHasher::encodeId(34);

    expect($hash)->toStartWith('h:')
        ->and(IdHasher::decodeId($hash))->toBe(34);
});

it('publishes the config file', function () {
    $paths = ServiceProvider::pathsToPublish(HashIdServiceProvider::class);

    expect($paths)->toHaveCount(1)
        ->and(array_values($paths)[0])->toBe(config_path('hashids.php'))
        ->and(array_keys($paths)[0])->toEndWith('config/hashids.php');
});

it('registers the manager as singleton', function () {
    expect(app(IdHasherManager::class))->toBe(app(IdHasherManager::class))
        ->and(app('idhasher'))->toBe(app(IdHasherManager::class));
});

it('publishes the config file with a tag', function () {
    expect(ServiceProvider::pathsToPublish(HashIdServiceProvider::class, 'hashid-config'))->toHaveCount(1);
});

it('reads per_model from the config', function () {
    expect(config('hashids.per_model'))->toBeTrue();
});

it('throws a clear error when GMP and BCMath are missing', function () {
    $manager = new class extends IdHasherManager
    {
        protected function hasMathExtension(): bool
        {
            return false;
        }
    };

    $manager->encodeId(1);
})->throws(RuntimeException::class, 'either GMP or BCMath must be installed');

it('does not check the extensions before hashing', function () {
    $manager = new class extends IdHasherManager
    {
        protected function hasMathExtension(): bool
        {
            return false;
        }
    };

    expect($manager->prefix())->toBe('h:');
});

it('accepts config values from the env as strings', function () {
    $this->setHashConfig(['length' => '10', 'prefix' => '', 'per_model' => 'false']);

    expect(strlen(app(IdHasherManager::class)->encodeId(1)))->toBe(10);
});
