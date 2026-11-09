<?php

declare(strict_types=1);

use TobyMaxham\HashId\Rules\HashId;
use TobyMaxham\HashId\Facades\IdHasher;
use Illuminate\Support\Facades\Validator;
use TobyMaxham\HashId\Rules\HashIdExists;
use TobyMaxham\HashId\Tests\Fixtures\Review;
use TobyMaxham\HashId\Tests\Fixtures\Product;
use TobyMaxham\HashId\Tests\Fixtures\PlainModel;

function passes(mixed $value, $rule): bool
{
    return Validator::make(['product' => $value], ['product' => [$rule]])->passes();
}

describe('HashId', function () {
    it('passes for a valid hash of the model', function () {
        expect(passes(IdHasher::for(Product::class)->encodeId(1), new HashId(Product::class)))->toBeTrue();
    });

    it('does not query the database', function () {
        DB::enableQueryLog();
        passes(IdHasher::for(Product::class)->encodeId(999), new HashId(Product::class));

        expect(DB::getQueryLog())->toBeEmpty();
    });

    it('fails for invalid values', function (mixed $value) {
        expect(passes($value, new HashId(Product::class)))->toBeFalse();
    })->with([
        'garbage'     => 'h:invalid',
        'raw id'      => '1',
        'int'         => 1,
        'array'       => [['h:abc']],
        'other model' => fn () => IdHasher::for(Review::class)->encodeId(1),
        'global hash' => fn () => IdHasher::encodeId(1),
    ]);

    it('uses the global hasher without a model', function () {
        expect(passes(IdHasher::encodeId(1), new HashId))->toBeTrue()
            ->and(passes(IdHasher::for(Product::class)->encodeId(1), new HashId))->toBeFalse();
    });

    it('accepts a model instance or a key', function () {
        $hash = IdHasher::for(Product::class)->encodeId(1);

        expect(passes($hash, new HashId(new Product)))->toBeTrue()
            ->and(passes($hash, new HashId('products')))->toBeTrue();
    });

    it('uses the translated exists message', function () {
        $validator = Validator::make(['product' => 'h:invalid'], ['product' => [new HashId(Product::class)]]);

        expect($validator->errors()->first('product'))->toBe('The selected product is invalid.');
    });

    it('works with nullable and optional fields', function () {
        $validator = Validator::make(['product' => null], ['product' => ['nullable', new HashId(Product::class)]]);

        expect($validator->passes())->toBeTrue();
    });
});

describe('HashIdExists', function () {
    it('passes for an existing record', function () {
        $product = Product::create(['name' => 'Laptop']);

        expect(passes($product->hashID(), new HashIdExists(Product::class)))->toBeTrue();
    });

    it('fails for a valid hash of a missing record', function () {
        expect(passes(IdHasher::for(Product::class)->encodeId(999), new HashIdExists(Product::class)))->toBeFalse();
    });

    it('fails for an invalid hash without a query', function () {
        DB::enableQueryLog();

        expect(passes('h:invalid', new HashIdExists(Product::class)))->toBeFalse()
            ->and(DB::getQueryLog())->toBeEmpty();
    });

    it('respects soft deletes', function () {
        $product = Product::create(['name' => 'Laptop']);
        $product->delete();

        expect(passes($product->hashID(), new HashIdExists(Product::class)))->toBeFalse()
            ->and(passes($product->hashID(), (new HashIdExists(Product::class))->where(fn ($q) => $q->withTrashed())))->toBeTrue();
    });

    it('adds constraints with where()', function () {
        $product = Product::create(['name' => 'Laptop']);

        expect(passes($product->hashID(), (new HashIdExists(Product::class))->where(fn ($q) => $q->where('name', 'Laptop'))))->toBeTrue()
            ->and(passes($product->hashID(), (new HashIdExists(Product::class))->where(fn ($q) => $q->where('name', 'Phone'))))->toBeFalse();
    });

    it('works for models without the Hasher trait', function () {
        expect(passes(IdHasher::for(PlainModel::class)->encodeId(1), new HashId(PlainModel::class)))->toBeTrue();
    });

    it('only accepts model classes', function () {
        new HashIdExists('products');
    })->throws(InvalidArgumentException::class, '[products] is not an Eloquent model.');
});
