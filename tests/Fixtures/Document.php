<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Tests\Fixtures;

use TobyMaxham\HashId\Hasher;
use Illuminate\Database\Eloquent\Model;

/**
 * String (UUID) primary key without HasUuids,
 * because HasUuids and Hasher both define resolveRouteBindingQuery().
 */
class Document extends Model
{
    use Hasher;

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
