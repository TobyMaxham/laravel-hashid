<?php

use TobyMaxham\HashId\IdHasherManager;
use Illuminate\Support\ServiceProvider;
use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\HashIdServiceProvider;
use TobyMaxham\HashId\Exceptions\WrongIdException;

it('merges the package config', function () {
    expect(config('hashids'))->toMatchArray([
        'prefix'            => 'h:',
        'prefix_mode'       => 'strict',
        'length'            => 18,
        'exception'         => WrongIdException::class,
        'exception_message' => 'WrongIdException',
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
