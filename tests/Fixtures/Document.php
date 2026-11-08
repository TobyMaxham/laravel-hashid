<?php

namespace TobyMaxham\HashId\Tests\Fixtures;

use TobyMaxham\HashId\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Document extends Model
{
    use Hasher;
    use HasUuids;

    public $timestamps = false;

    protected $guarded = [];
}
