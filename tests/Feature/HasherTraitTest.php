<?php

use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\Interfaces\Hashing;
use TobyMaxham\HashId\Tests\Fixtures\Product;
use TobyMaxham\HashId\Tests\Fixtures\Document;
use TobyMaxham\HashId\Tests\Fixtures\BaseProduct;

it('uses the hash as route key', function () {
    $product = Product::create(['name' => 'A']);

    expect($product->getRouteKey())->toBe(IdHasher::encodeId($product->id));
});

it('returns the hash from hashID()', function () {
    $product = Product::create(['name' => 'A']);

    expect($product->hashID())->toBe($product->getRouteKey())
        ->and($product)->toBeInstanceOf(Hashing::class);
});

it('returns the hash without prefix from safeHashID()', function () {
    $product = Product::create(['name' => 'A']);

    expect((string) $product->safeHashID())
        ->toBe(substr($product->hashID(), strlen('h:')))
        ->not->toStartWith('h:');
});

it('returns the full hash from safeHashID() when the prefix is empty', function () {
    $this->setHashConfig(['prefix' => '']);
    $product = Product::create(['name' => 'A']);

    expect((string) $product->safeHashID())->toBe($product->hashID());
});

it('resolves the model from a hash', function () {
    $product = Product::create(['name' => 'A']);

    $resolved = (new Product)->resolveRouteBinding($product->hashID());

    expect($resolved->is($product))->toBeTrue();
});

it('returns null for an invalid hash', function (string $value) {
    Product::create(['name' => 'A']);

    expect((new Product)->resolveRouteBinding($value))->toBeNull();
})->with([
    'garbage' => 'h:invalid',
    'raw id'  => '1',
]);

it('returns null for a hash without the prefix', function () {
    $product = Product::create(['name' => 'A']);

    expect((new Product)->resolveRouteBinding((string) $product->safeHashID()))->toBeNull();
});

it('returns null for a valid hash of a missing record', function () {
    expect((new Product)->resolveRouteBinding(IdHasher::encodeId(999)))->toBeNull();
});

it('returns null instead of throwing even when exceptions are enabled', function () {
    config()->set('hashids.disable_exception', false);

    expect((new Product)->resolveRouteBinding('h:invalid'))->toBeNull();
});

it('works the same with the ModelWithHasher base class', function () {
    $product = BaseProduct::create(['name' => 'A']);

    expect($product->hashID())->toBe(IdHasher::encodeId($product->id))
        ->and((new BaseProduct)->resolveRouteBinding($product->hashID())->is($product))->toBeTrue();
});

it('creates the same hash for the same id in different models', function () {
    // Documents the current behavior. v2 plans a different hash per model.
    $product = BaseProduct::create(['name' => 'A']);
    $other = Product::find($product->id);

    expect($product->hashID())->toBe($other->hashID());
});

it('cannot hash non-integer keys yet', function () {
    // Documents the current behavior: UUID keys produce an empty hash.
    $document = Document::create(['name' => 'A']);

    expect($document->hashID())->toBe('h:');
});
