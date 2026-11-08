<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Tests\Fixtures;

use TobyMaxham\HashId\Hasher;
use Illuminate\Database\Eloquent\Model;
use TobyMaxham\HashId\Interfaces\Hashing;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model implements Hashing
{
    use Hasher;
    use SoftDeletes;

    public $timestamps = false;

    protected $guarded = [];

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
