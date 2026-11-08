<?php

use Illuminate\Support\Facades\Route;
use TobyMaxham\HashId\IdHasherManager;
use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\Tests\Fixtures\Review;
use TobyMaxham\HashId\Tests\Fixtures\Product;
use TobyMaxham\HashId\Tests\Fixtures\PlainModel;
use TobyMaxham\HashId\Tests\Fixtures\KeyedByMethod;
use Illuminate\Routing\Middleware\SubstituteBindings;
use TobyMaxham\HashId\Tests\Fixtures\KeyedByConstant;
use TobyMaxham\HashId\Tests\Fixtures\KeyedByProperty;

describe('hashes per model', function () {
    it('creates different hashes for the same id in different models', function () {
        $product = Product::create(['name' => 'A']);
        $review = Review::create(['id' => $product->id, 'product_id' => $product->id, 'body' => 'B']);

        expect($product->id)->toBe($review->id)
            ->and($product->hashID())->not->toBe($review->hashID())
            ->and($product->hashID())->not->toBe(IdHasher::encodeId($product->id));
    });

    it('does not decode the hash of another model', function () {
        $product = Product::create(['name' => 'A']);

        expect(IdHasher::for(Review::class)->decodeId($product->hashID(), false))->toBeNull()
            ->and(IdHasher::decodeId($product->hashID(), false))->toBeNull()
            ->and(IdHasher::for(Product::class)->decodeId($product->hashID()))->toBe($product->id);
    });

    it('returns 404 when the hash of another model is used in a route', function () {
        Route::middleware(SubstituteBindings::class)
            ->get('products/{product}', fn (Product $product) => $product->name);

        $product = Product::create(['name' => 'A']);
        $review = Review::create(['id' => $product->id, 'product_id' => $product->id, 'body' => 'B']);

        $this->get('products/'.$product->hashID())->assertOk();
        $this->get('products/'.$review->hashID())->assertNotFound();
    });

    it('shares the hashes of all models when per_model is disabled', function () {
        $this->setHashConfig(['per_model' => false]);
        $product = Product::create(['name' => 'A']);

        expect($product->hashID())->toBe(IdHasher::encodeId($product->id))
            ->and(IdHasher::for(Review::class)->encodeId($product->id))->toBe($product->hashID());
    });
});

describe('model key', function () {
    it('defaults to the table name', function () {
        expect((new Product)->hashIdKey())->toBe('products')
            ->and(IdHasher::for(Product::class)->encodeId(1))->toBe(IdHasher::for('products')->encodeId(1));
    });

    it('can be set with a constant, a property or a method', function (string $class) {
        $model = new $class;

        expect($model->hashIdKey())->toBe('renamed-products')
            ->and($model->hashIdManager()->encodeIdWithoutPrefix(1))
            ->toBe(IdHasher::for('renamed-products')->encodeIdWithoutPrefix(1));
    })->with([KeyedByConstant::class, KeyedByProperty::class, KeyedByMethod::class]);

    it('keeps the hashes when the table name changes', function () {
        $before = IdHasher::for(KeyedByConstant::class)->encodeId(1);

        $model = new KeyedByConstant;
        $model->setTable('new_products');

        expect($model->hashIdKey())->toBe('renamed-products')
            ->and($model->hashIdManager()->encodeId(1))->toBe($before);
    });

    it('uses the table name of models without the Hasher trait', function () {
        expect(IdHasher::for(PlainModel::class)->encodeId(1))->toBe(IdHasher::for('plain_models')->encodeId(1));
    });

    it('accepts model instances and class names', function () {
        expect(IdHasher::for(new Product))->toBe(IdHasher::for(Product::class));
    });
});

describe('model prefix', function () {
    it('uses the global prefix by default', function () {
        expect((new Product)->hashIdPrefix())->toBeNull()
            ->and(Product::create(['name' => 'A'])->hashID())->toStartWith('h:');
    });

    it('can be set with a constant', function () {
        $hash = IdHasher::for(KeyedByConstant::class)->encodeId(1);

        expect($hash)->toStartWith('prd_')
            ->and(IdHasher::for(KeyedByConstant::class)->decodeId($hash))->toBe(1);
    });

    it('can be set with a method', function () {
        expect(IdHasher::for(KeyedByMethod::class)->encodeId(1))->toStartWith('m_');
    });

    it('can be disabled per model with an empty string', function () {
        $manager = IdHasher::for(KeyedByProperty::class);

        expect($manager->prefix())->toBe('')
            ->and($manager->encodeId(1))->toBe($manager->encodeIdWithoutPrefix(1));
    });

    it('works in routes', function () {
        Route::middleware(SubstituteBindings::class)
            ->get('keyed/{product}', fn (KeyedByConstant $product) => $product->name);

        $product = KeyedByConstant::create(['name' => 'Laptop']);

        expect($product->hashID())->toStartWith('prd_');
        $this->get('keyed/'.$product->hashID())->assertOk()->assertSee('Laptop');
        $this->get('keyed/h:'.$product->safeHashID())->assertNotFound();
    });

    it('inherits the prefix mode', function () {
        $this->setHashConfig(['prefix_mode' => 'optional']);
        $manager = IdHasher::for(KeyedByConstant::class);

        expect($manager->prefixMode())->toBe(IdHasherManager::PREFIX_OPTIONAL)
            ->and($manager->decodeId($manager->encodeIdWithoutPrefix(1)))->toBe(1);
    });
});

describe('caching', function () {
    it('returns the same instance for the same model', function () {
        expect(IdHasher::for(Product::class))->toBe(IdHasher::for(Product::class))
            ->and(IdHasher::for(Product::class))->not->toBe(IdHasher::for(Review::class));
    });

    it('creates the hasher only once per model', function () {
        $manager = IdHasher::for(Product::class);
        $hasher = (fn () => $this->hasher())->call($manager);

        $manager->encodeId(1);

        expect((fn () => $this->hasher())->call($manager))->toBe($hasher);
    });
});
