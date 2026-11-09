<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Tests\Fixtures;

use TobyMaxham\HashId\SerializesHashIds;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SerializedReview extends Review
{
    use SerializesHashIds;

    protected $table = 'reviews';

    /** @var array<string, class-string> */
    protected array $hashIdForeignKeys = ['product_id' => Product::class];

    /** @return BelongsTo<SerializedProduct, $this> */
    public function serializedProduct(): BelongsTo
    {
        return $this->belongsTo(SerializedProduct::class, 'product_id');
    }
}
