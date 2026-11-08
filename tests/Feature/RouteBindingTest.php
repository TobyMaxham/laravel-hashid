<?php

use Illuminate\Support\Facades\Route;
use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\Tests\Fixtures\Review;
use TobyMaxham\HashId\Tests\Fixtures\Product;
use Illuminate\Routing\Middleware\SubstituteBindings;

beforeEach(function () {
    Route::middleware(SubstituteBindings::class)->group(function () {
        Route::get('products/{product}', fn (Product $product) => $product->name)->name('products.show');
        Route::get('trashed/{product}', fn (Product $product) => $product->name)->withTrashed();
        Route::get('slug/{product:slug}', fn (Product $product) => $product->name);
        Route::get('products/{product}/reviews/{review}', fn (Product $product, Review $review) => $review->body)->scopeBindings();
    });
});

it('generates urls with the hash', function () {
    $product = Product::create(['name' => 'A']);

    expect(route('products.show', $product))->toEndWith('/products/'.$product->hashID());
});

it('resolves a model from the hash', function () {
    $product = Product::create(['name' => 'Laptop']);

    $this->get('products/'.$product->hashID())->assertOk()->assertSee('Laptop');
});

it('returns 404 for invalid values', function (string $value) {
    Product::create(['name' => 'Laptop']);

    $this->get('products/'.$value)->assertNotFound();
})->with([
    'raw id'     => '1',
    'garbage'    => 'h:invalid',
    'unknown id' => fn () => IdHasher::encodeId(999),
]);

it('works without a prefix', function () {
    $this->setHashConfig(['prefix' => '']);
    $product = Product::create(['name' => 'Laptop']);

    expect($product->hashID())->not->toContain(':');
    $this->get('products/'.$product->hashID())->assertOk()->assertSee('Laptop');
});

it('returns 404 for soft deleted models', function () {
    $product = Product::create(['name' => 'Laptop']);
    $product->delete();

    $this->get('products/'.$product->hashID())->assertNotFound();
});

// Known bugs, see New-Version.md section 9. Remove skip() once fixed.

it('resolves routes with a custom field like {product:slug}', function () {
    Product::create(['name' => 'Laptop', 'slug' => 'laptop']);

    $this->get('slug/laptop')->assertOk()->assertSee('Laptop');
})->skip('Bug: custom route fields are decoded as hash (New-Version.md 9.1)');

it('resolves scoped child bindings', function () {
    $product = Product::create(['name' => 'Laptop']);
    $review = $product->reviews()->create(['body' => 'Great']);

    $this->get("products/{$product->hashID()}/reviews/{$review->hashID()}")->assertOk()->assertSee('Great');
})->skip('Bug: scoped child bindings ignore the hash (New-Version.md 9.2)');

it('accepts the safeHashID in routes', function () {
    $product = Product::create(['name' => 'Laptop']);

    $this->get('products/'.$product->safeHashID())->assertOk();
})->skip('Issue #6: needs prefix_mode=optional (New-Version.md 2)');

it('resolves soft deleted models on withTrashed routes', function () {
    $product = Product::create(['name' => 'Laptop']);
    $product->delete();

    $this->get('trashed/'.$product->hashID())->assertOk()->assertSee('Laptop');
})->skip('Bug: withTrashed routes ignore the hash (New-Version.md 9.2)');
