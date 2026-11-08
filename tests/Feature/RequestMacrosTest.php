<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\Tests\Fixtures\Product;

function requestWith(array $input): Request
{
    return Request::create('/', 'POST', $input);
}

it('decodes a hash from the request', function () {
    $hash = IdHasher::for(Product::class)->encodeId(34);

    expect(requestWith(['product_id' => $hash])->hashId('product_id', Product::class))->toBe(34);
});

it('returns null for invalid or missing values', function (array $input) {
    expect(requestWith($input)->hashId('product_id', Product::class))->toBeNull();
})->with([
    'missing'     => [[]],
    'invalid'     => [['product_id' => 'h:invalid']],
    'array'       => [['product_id' => ['h:abc']]],
    'other model' => fn () => [['product_id' => IdHasher::encodeId(34)]],
]);

it('uses the global hasher without a model', function () {
    expect(requestWith(['id' => IdHasher::encodeId(34)])->hashId('id'))->toBe(34);
});

it('reads nested keys', function () {
    $hash = IdHasher::for(Product::class)->encodeId(34);

    expect(requestWith(['order' => ['product' => $hash]])->hashId('order.product', Product::class))->toBe(34);
});

it('decodes several hashes and skips invalid ones', function () {
    $hasher = IdHasher::for(Product::class);
    $request = requestWith(['ids' => [$hasher->encodeId(1), 'h:invalid', ['x'], $hasher->encodeId(2), $hasher->encodeId(1)]]);

    expect($request->hashIds('ids', Product::class))->toBe([1, 2])
        ->and(requestWith([])->hashIds('ids', Product::class))->toBe([])
        ->and(requestWith(['ids' => IdHasher::encodeId(5)])->hashIds('ids'))->toBe([5]);
});
