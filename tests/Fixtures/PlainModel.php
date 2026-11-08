<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;

/** A model without the Hasher trait. */
class PlainModel extends Model
{
    protected $table = 'plain_models';
}
