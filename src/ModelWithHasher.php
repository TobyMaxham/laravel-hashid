<?php

declare(strict_types=1);

namespace TobyMaxham\HashId;

use Illuminate\Database\Eloquent\Model;

abstract class ModelWithHasher extends Model
{
    use Hasher;
}
