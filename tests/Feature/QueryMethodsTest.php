<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use TobyMaxham\HashId\Tests\Fixtures\Review;
use TobyMaxham\HashId\Tests\Fixtures\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

beforeEach(function () {
    $this->laptop = Product::create(['name' => 'Laptop']);
    $this->phone = Product::create(['name' => 'Phone']);
    $this->tablet = Product::create(['name' => 'Tablet']);
});

function queryCount(callable $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

describe('decodeHashId', function () {
    it('decodes the hash of the model', function () {
        expect(Product::decodeHashId($this->laptop->hashID()))->toBe($this->laptop->id);
    });

    it('returns null for invalid hashes', function (mixed $hash) {
        expect(Product::decodeHashId($hash))->toBeNull();
    })->with(['h:invalid', '1', '', null]);

    it('returns null for the hash of another model', function () {
        $review = $this->laptop->reviews()->create(['body' => 'Great']);

        expect(Product::decodeHashId($review->hashID()))->toBeNull();
    });

    it('does not query the database', function () {
        expect(queryCount(fn () => Product::decodeHashId($this->laptop->hashID())))->toBe(0);
    });

    it('decodes several hashes and skips invalid ones', function () {
        $ids = Product::decodeHashIds([$this->laptop->hashID(), 'h:invalid', $this->phone->hashID(), $this->laptop->hashID()]);

        expect($ids)->toBe([$this->laptop->id, $this->phone->id]);
    });
});

describe('findByHashId', function () {
    it('finds the model', function () {
        expect(Product::findByHashId($this->phone->hashID())->is($this->phone))->toBeTrue();
    });

    it('returns null for invalid hashes without a query', function () {
        expect(queryCount(fn () => expect(Product::findByHashId('h:invalid'))->toBeNull()))->toBe(0);
    });

    it('returns null for a valid hash of a missing record', function () {
        $hash = $this->tablet->hashID();
        $this->tablet->forceDelete();

        expect(Product::findByHashId($hash))->toBeNull();
    });

    it('selects only the given columns', function () {
        $product = Product::findByHashId($this->laptop->hashID(), ['id']);

        expect($product->getAttributes())->toBe(['id' => $this->laptop->id]);
    });

    it('respects global scopes like soft deletes', function () {
        $this->laptop->delete();

        expect(Product::findByHashId($this->laptop->hashID()))->toBeNull();
    });

    it('accepts the hash without prefix in optional mode', function () {
        $this->setHashConfig(['prefix_mode' => 'optional']);

        expect(Product::findByHashId($this->laptop->safeHashID())->is($this->laptop))->toBeTrue();
    });
});

describe('findByHashIdOrFail', function () {
    it('finds the model', function () {
        expect(Product::findByHashIdOrFail($this->phone->hashID())->is($this->phone))->toBeTrue();
    });

    it('throws ModelNotFoundException for invalid hashes', function () {
        try {
            Product::findByHashIdOrFail('h:invalid');
        } catch (ModelNotFoundException $e) {
            expect($e->getModel())->toBe(Product::class)
                ->and($e->getIds())->toBe(['h:invalid']);

            return;
        }

        $this->fail('ModelNotFoundException was not thrown');
    });

    it('throws ModelNotFoundException for missing records', function () {
        $hash = $this->tablet->hashID();
        $this->tablet->forceDelete();

        Product::findByHashIdOrFail($hash);
    })->throws(ModelNotFoundException::class);
});

describe('findManyByHashIds', function () {
    it('finds all models and skips invalid hashes', function () {
        $products = Product::findManyByHashIds([$this->laptop->hashID(), 'h:invalid', $this->tablet->hashID()]);

        expect($products->modelKeys())->toEqualCanonicalizing([$this->laptop->id, $this->tablet->id]);
    });

    it('accepts any iterable', function () {
        $products = Product::findManyByHashIds(collect([$this->laptop->hashID()]));

        expect($products)->toHaveCount(1);
    });

    it('returns an empty collection without a query when no hash is valid', function () {
        $count = queryCount(function () {
            expect(Product::findManyByHashIds(['h:invalid', '1']))
                ->toBeInstanceOf(Collection::class)
                ->toBeEmpty();
        });

        expect($count)->toBe(0);
    });
});

describe('scopes', function () {
    it('filters by hash with whereHashId', function () {
        expect(Product::whereHashId($this->phone->hashID())->pluck('name')->all())->toBe(['Phone']);
    });

    it('matches no rows for an invalid hash', function () {
        expect(Product::whereHashId('h:invalid')->count())->toBe(0);
    });

    it('combines with orWhereHashId', function () {
        $names = Product::whereHashId($this->laptop->hashID())
            ->orWhereHashId($this->tablet->hashID())
            ->orderBy('id')
            ->pluck('name')
            ->all();

        expect($names)->toBe(['Laptop', 'Tablet']);
    });

    it('filters by several hashes with whereHashIdIn', function () {
        $names = Product::whereHashIdIn([$this->laptop->hashID(), 'h:invalid', $this->phone->hashID()])
            ->orderBy('id')
            ->pluck('name')
            ->all();

        expect($names)->toBe(['Laptop', 'Phone'])
            ->and(Product::whereHashIdIn(['h:invalid'])->count())->toBe(0);
    });

    it('excludes hashes with whereHashIdNot and whereHashIdNotIn', function () {
        expect(Product::whereHashIdNot($this->laptop->hashID())->count())->toBe(2)
            ->and(Product::whereHashIdNotIn([$this->laptop->hashID(), $this->phone->hashID()])->pluck('name')->all())->toBe(['Tablet'])
            ->and(Product::whereHashIdNot('h:invalid')->count())->toBe(3);
    });

    it('works inside whereHas', function () {
        $this->laptop->reviews()->create(['body' => 'Great']);
        $this->phone->reviews()->create(['body' => 'Okay']);

        $bodies = Review::whereHas('product', fn ($query) => $query->whereHashId($this->laptop->hashID()))
            ->pluck('body')
            ->all();

        expect($bodies)->toBe(['Great']);
    });

    it('works on relations', function () {
        $review = $this->laptop->reviews()->create(['body' => 'Great']);
        $this->laptop->reviews()->create(['body' => 'Bad']);

        expect($this->laptop->reviews()->whereHashId($review->hashID())->pluck('body')->all())->toBe(['Great'])
            ->and($this->phone->reviews()->whereHashId($review->hashID())->exists())->toBeFalse();
    });

    it('uses the qualified key, so joins are not ambiguous', function () {
        $this->laptop->reviews()->create(['body' => 'Great']);

        $names = Product::query()
            ->join('reviews', 'reviews.product_id', '=', 'products.id')
            ->whereHashId($this->laptop->hashID())
            ->pluck('products.name')
            ->all();

        expect($names)->toBe(['Laptop']);
    });

    it('works with withTrashed()', function () {
        $this->laptop->delete();

        expect(Product::whereHashId($this->laptop->hashID())->exists())->toBeFalse()
            ->and(Product::withTrashed()->whereHashId($this->laptop->hashID())->exists())->toBeTrue();
    });
});
