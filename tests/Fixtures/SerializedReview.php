<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Tests\Fixtures;

use TobyMaxham\HashId\SerializesHashIds;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerializedReview extends Review
{
    use SerializesHashIds;

    protected $table = 'reviews';

    protected array $hashIdForeignKeys = ['product_id' => Product::class];

    public function product(): BelongsTo
    {
        return $this->belongsTo(SerializedProduct::class, 'product_id');
    }
}
