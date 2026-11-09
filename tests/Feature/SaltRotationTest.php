<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use TobyMaxham\HashId\IdHasherManager;
use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\Tests\Fixtures\Review;
use TobyMaxham\HashId\Tests\Fixtures\Product;
use Illuminate\Routing\Middleware\SubstituteBindings;

function hashWithSalt(string $salt, int $id, ?string $model = null): string
{
    test()->setHashConfig(['salt' => $salt, 'previous_salts' => '']);
    $hash = null === $model ? IdHasher::encodeId($id) : IdHasher::for($model)->encodeId($id);

    return $hash;
}

describe('previous_salts', function () {
    it('decodes hashes of an old salt and encodes with the new one', function () {
        $old = hashWithSalt('old-salt', 34);

        $this->setHashConfig(['salt' => 'new-salt', 'previous_salts' => 'old-salt']);

        expect(IdHasher::decodeId($old))->toBe(34)
            ->and(IdHasher::encodeId(34))->not->toBe($old)
            ->and(IdHasher::encodeId(34))->toBe(hashWithSalt('new-salt', 34));
    });

    it('rejects hashes of an old salt that is not listed', function () {
        $old = hashWithSalt('old-salt', 34);

        $this->setHashConfig(['salt' => 'new-salt', 'previous_salts' => '']);

        expect(IdHasher::decodeId($old, false))->toBeNull();
    });

    it('works per model', function () {
        $old = hashWithSalt('old-salt', 34, Product::class);

        $this->setHashConfig(['salt' => 'new-salt', 'previous_salts' => 'old-salt']);

        expect(IdHasher::for(Product::class)->decodeId($old))->toBe(34)
            ->and(IdHasher::for(Review::class)->decodeId($old, false))->toBeNull();
    });

    it('works in routes', function () {
        Route::middleware(SubstituteBindings::class)->get('products/{product}', fn (Product $product) => $product->name);
        $product = Product::create(['name' => 'Laptop']);
        $old = hashWithSalt('old-salt', $product->id, Product::class);

        $this->setHashConfig(['salt' => 'new-salt', 'previous_salts' => 'old-salt']);

        $this->get('products/'.$old)->assertOk()->assertSee('Laptop');
        expect($product->fresh()->hashID())->not->toBe($old);
    });

    it('reads a comma separated string and an array', function (mixed $value) {
        config()->set('hashids.previous_salts', $value);

        expect((new IdHasherManager(null, null, 'current'))->fallbackSalts())->toBe(['a', 'b']);
    })->with([
        'string'            => 'a, b,,',
        'array'             => [['a', 'b', '']],
        'with current salt' => 'a,current,b,a',
    ]);

    it('has no fallback salts by default', function () {
        expect(IdHasher::getFacadeRoot()->fallbackSalts())->toBe([]);
    });

    it('creates the fallback hashers only when needed', function () {
        $this->setHashConfig(['previous_salts' => 'old-salt']);
        $manager = app(IdHasherManager::class);

        $manager->decodeId($manager->encodeId(1));
        $before = (fn () => $this->fallbackHashers)->call($manager);

        $manager->decodeId('h:invalid', false);
        $after = (fn () => $this->fallbackHashers)->call($manager);

        expect($before)->toBeNull()->and($after)->toHaveCount(1);
    });
});

describe('accept_legacy_hashes', function () {
    it('accepts the global (1.x) hashes for models when enabled', function () {
        $legacy = hashWithSalt('test-salt', 34);

        $this->setHashConfig(['salt' => 'test-salt', 'accept_legacy_hashes' => true]);

        expect(IdHasher::for(Product::class)->decodeId($legacy))->toBe(34)
            ->and(IdHasher::for(Product::class)->encodeId(34))->not->toBe($legacy);
    });

    it('rejects the global hashes for models by default', function () {
        $legacy = hashWithSalt('test-salt', 34);

        expect(IdHasher::for(Product::class)->decodeId($legacy, false))->toBeNull();
    });

    it('works in routes', function () {
        Route::middleware(SubstituteBindings::class)->get('products/{product}', fn (Product $product) => $product->name);
        $product = Product::create(['name' => 'Laptop']);
        $legacy = IdHasher::encodeId($product->id);

        $this->get('products/'.$legacy)->assertNotFound();

        $this->setHashConfig(['accept_legacy_hashes' => true]);

        $this->get('products/'.$legacy)->assertOk()->assertSee('Laptop');
        $this->get('products/'.$product->hashID())->assertOk();
    });

    it('also accepts legacy hashes of previous salts', function () {
        $legacy = hashWithSalt('old-salt', 34);

        $this->setHashConfig(['salt' => 'new-salt', 'previous_salts' => 'old-salt', 'accept_legacy_hashes' => true]);

        expect(IdHasher::for(Product::class)->decodeId($legacy))->toBe(34);
    });

    it('has no effect when per_model is disabled', function () {
        $this->setHashConfig(['per_model' => false, 'accept_legacy_hashes' => true]);

        expect(IdHasher::for(Product::class)->fallbackSalts())->toBe([]);
    });
});
