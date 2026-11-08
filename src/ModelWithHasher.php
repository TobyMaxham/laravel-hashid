<?php

declare(strict_types=1);

namespace TobyMaxham\HashId;

use Illuminate\Database\Eloquent\Model;
use TobyMaxham\HashId\Interfaces\HasHashId;

abstract class ModelWithHasher extends Model implements HasHashId
{
    use Hasher;
}
