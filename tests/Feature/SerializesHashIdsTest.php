<?php

declare(strict_types=1);

use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\Tests\Fixtures\Product;
use TobyMaxham\HashId\Tests\Fixtures\SerializedReview;
use TobyMaxham\HashId\Tests\Fixtures\SerializedProduct;

it('replaces the primary key with the hash', function () {
    $product = SerializedProduct::create(['name' => 'Laptop']);

    expect($product->toArray()['id'])->toBe($product->hashID())
        ->and(json_decode($product->toJson(), true)['id'])->toBe($product->hashID());
});

it('keeps the real id on the model', function () {
    $product = SerializedProduct::create(['name' => 'Laptop']);
    $product->toArray();

    expect($product->id)->toBeInt()
        ->and($product->getKey())->toBeInt();
});

it('replaces mapped foreign keys with the hash of the related model', function () {
    $product = Product::create(['name' => 'Laptop']);
    $review = SerializedReview::create(['product_id' => $product->id, 'body' => 'Great']);

    expect($review->toArray()['product_id'])->toBe($product->hashID())
        ->and($review->toArray()['product_id'])->toBe(IdHasher::for(Product::class)->encodeId($product->id));
});

it('keeps relations working', function () {
    $product = SerializedProduct::create(['name' => 'Laptop']);
    $review = SerializedReview::create(['product_id' => $product->id, 'body' => 'Great']);

    $loaded = SerializedReview::with('serializedProduct')->find($review->id);

    expect($loaded->serializedProduct->is($product))->toBeTrue()
        ->and($loaded->toArray()['serialized_product']['id'])->toBe($product->hashID());
});

it('hashes collections', function () {
    SerializedProduct::create(['name' => 'Laptop']);
    SerializedProduct::create(['name' => 'Phone']);

    $ids = SerializedProduct::all()->toArray();

    expect(array_column($ids, 'id'))->each->toStartWith('h:');
});

it('skips missing, hidden and null keys', function () {
    $product = SerializedProduct::create(['name' => 'Laptop']);
    $review = new SerializedReview(['body' => 'No product']);

    expect($product->makeHidden('id')->toArray())->not->toHaveKey('id')
        ->and((new SerializedProduct(['name' => 'New']))->toArray())->not->toHaveKey('id')
        ->and($review->toArray())->not->toHaveKey('product_id');
});

it('does not hash anything without the trait', function () {
    $product = Product::create(['name' => 'Laptop']);

    expect($product->toArray()['id'])->toBe($product->id);
});

it('hashes foreign keys that the database returns as strings', function () {
    $product = Product::create(['name' => 'Laptop']);
    $review = (new SerializedReview)->setRawAttributes(['id' => 1, 'product_id' => (string) $product->id, 'body' => 'x']);

    expect($review->toArray()['product_id'])->toBe($product->hashID());
});

it('does not hash non-numeric values', function () {
    $review = (new SerializedReview)->setRawAttributes(['id' => 1, 'product_id' => 'abc', 'body' => 'x']);

    expect($review->toArray()['product_id'])->toBe('abc');
});
