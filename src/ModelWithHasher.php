<?php

namespace TobyMaxham\HashId;

use Illuminate\Database\Eloquent\Model;

abstract class ModelWithHasher extends Model
{
    use Hasher;
}
