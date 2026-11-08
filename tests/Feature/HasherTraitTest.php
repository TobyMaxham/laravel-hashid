<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\Interfaces\Hashing;
use TobyMaxham\HashId\Tests\Fixtures\Product;
use TobyMaxham\HashId\Tests\Fixtures\Document;
use TobyMaxham\HashId\Tests\Fixtures\BaseProduct;
use Illuminate\Database\Eloquent\ModelNotFoundException;

it('uses the hash as route key', function () {
    $product = Product::create(['name' => 'A']);

    expect($product->getRouteKey())->toBe(IdHasher::for(Product::class)->encodeId($product->id));
});

it('returns the hash from hashID()', function () {
    $product = Product::create(['name' => 'A']);

    expect($product->hashID())->toBe($product->getRouteKey())
        ->and($product)->toBeInstanceOf(Hashing::class);
});

it('returns the hash without prefix from safeHashID()', function () {
    $product = Product::create(['name' => 'A']);

    expect($product->safeHashID())
        ->toBe(substr($product->hashID(), strlen('h:')))
        ->not->toStartWith('h:');
});

it('returns a plain string from safeHashID()', function () {
    $product = Product::create(['name' => 'A']);

    expect($product->safeHashID())->toBeString();
});

it('returns the full hash from safeHashID() when the prefix is empty', function () {
    $this->setHashConfig(['prefix' => '']);
    $product = Product::create(['name' => 'A']);

    expect($product->safeHashID())->toBe($product->hashID());
});

it('resolves the model from a hash', function () {
    $product = Product::create(['name' => 'A']);

    $resolved = (new Product)->resolveRouteBinding($product->hashID());

    expect($resolved->is($product))->toBeTrue();
});

it('throws ModelNotFoundException for an invalid hash', function (string $value) {
    Product::create(['name' => 'A']);

    (new Product)->resolveRouteBinding($value);
})->with([
    'garbage' => 'h:invalid',
    'raw id'  => '1',
])->throws(ModelNotFoundException::class);

it('throws ModelNotFoundException for a hash without the prefix', function () {
    $product = Product::create(['name' => 'A']);

    (new Product)->resolveRouteBinding($product->safeHashID());
})->throws(ModelNotFoundException::class);

it('does not query the database for an invalid hash', function () {
    DB::enableQueryLog();

    try {
        (new Product)->resolveRouteBinding('h:invalid');
    } catch (ModelNotFoundException) {
    }

    expect(DB::getQueryLog())->toBeEmpty();
});

it('returns null for a valid hash of a missing record', function () {
    expect((new Product)->resolveRouteBinding(IdHasher::for(Product::class)->encodeId(999)))->toBeNull();
});

it('throws ModelNotFoundException instead of the configured exception', function () {
    config()->set('hashids.disable_exception', false);

    (new Product)->resolveRouteBinding('h:invalid');
})->throws(ModelNotFoundException::class);

it('does not decode custom route fields', function () {
    $product = Product::create(['name' => 'A', 'slug' => 'laptop']);

    expect((new Product)->resolveRouteBinding('laptop', 'slug')->is($product))->toBeTrue();
});

it('decodes the route key also when it is passed explicitly', function (string $field) {
    $product = Product::create(['name' => 'A']);

    expect((new Product)->resolveRouteBinding($product->hashID(), $field)->is($product))->toBeTrue();
})->with(['id', 'products.id']);

it('resolves soft deleted models with resolveSoftDeletableRouteBinding()', function () {
    $product = Product::create(['name' => 'A']);
    $product->delete();

    expect((new Product)->resolveSoftDeletableRouteBinding($product->hashID())->is($product))->toBeTrue();
});

it('works the same with the ModelWithHasher base class', function () {
    $product = BaseProduct::create(['name' => 'A']);

    expect($product->hashID())->toBe(IdHasher::for(BaseProduct::class)->encodeId($product->id))
        ->and((new BaseProduct)->resolveRouteBinding($product->hashID())->is($product))->toBeTrue();
});

it('creates the same hash for models with the same table', function () {
    $product = BaseProduct::create(['name' => 'A']);
    $other = Product::find($product->id);

    expect($product->getTable())->toBe($other->getTable())
        ->and($product->hashID())->toBe($other->hashID());
});

it('cannot hash non-integer keys yet', function () {
    // Documents the current behavior: UUID keys produce an empty hash.
    $document = Document::create(['id' => (string) Str::uuid(), 'name' => 'A']);

    expect($document->hashID())->toBe('h:');
});
