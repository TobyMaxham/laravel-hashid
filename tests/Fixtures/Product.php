<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Tests\Fixtures;

use TobyMaxham\HashId\Hasher;
use Illuminate\Database\Eloquent\Model;
use TobyMaxham\HashId\Interfaces\HasHashId;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model implements HasHashId
{
    use Hasher;
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];

    /** @return HasMany<Review, $this> */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
