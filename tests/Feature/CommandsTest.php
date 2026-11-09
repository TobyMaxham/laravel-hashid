<?php

declare(strict_types=1);

use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\Tests\Fixtures\Product;

it('encodes ids with the global salt', function () {
    $this->artisan('hashid:encode', ['id' => ['1', '34']])
        ->expectsTable(['ID', 'Hash'], [['1', IdHasher::encodeId(1)], ['34', IdHasher::encodeId(34)]])
        ->assertSuccessful();
});

it('encodes ids for a model', function () {
    $this->artisan('hashid:encode', ['id' => ['34'], '--model' => Product::class])
        ->expectsTable(['ID', 'Hash'], [['34', IdHasher::for(Product::class)->encodeId(34)]])
        ->assertSuccessful();
});

it('encodes ids for a key', function () {
    $this->artisan('hashid:encode', ['id' => ['34'], '--model' => 'products'])
        ->expectsTable(['ID', 'Hash'], [['34', IdHasher::for(Product::class)->encodeId(34)]])
        ->assertSuccessful();
});

it('fails for values that are not integers', function () {
    $this->artisan('hashid:encode', ['id' => ['abc']])->assertFailed();
});

it('decodes hashes', function () {
    $hash = IdHasher::for(Product::class)->encodeId(34);

    $this->artisan('hashid:decode', ['hash' => [$hash], '--model' => Product::class])
        ->expectsTable(['Hash', 'ID'], [[$hash, '34']])
        ->assertSuccessful();
});

it('fails for invalid hashes', function () {
    $this->artisan('hashid:decode', ['hash' => ['h:invalid']])->assertFailed();
});

it('fails for unknown model classes', function (string $command, string $argument) {
    $this->artisan($command, [$argument => ['1'], '--model' => 'App\\Models\\Missing'])
        ->expectsOutputToContain('Model class [App\\Models\\Missing] does not exist.')
        ->assertFailed();
})->with([['hashid:encode', 'id'], ['hashid:decode', 'hash']]);
