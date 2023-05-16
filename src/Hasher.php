<?php

namespace TobyMaxham\HashId;

/**
 * @mixin \Illuminate\Database\Eloquent\Model
 */
trait Hasher
{
    public function getRouteKey()
    {
        return app(IdHasher::class)->encodeId(parent::getRouteKey());
    }

    public function resolveRouteBinding($value, $field = null): ?\Illuminate\Database\Eloquent\Model
    {
        $id = $this->decodeId($value);

        return parent::resolveRouteBinding($id, $field);
    }

    protected function decodeId($value)
    {
        return app(IdHasher::class)->decodeId($value);
    }

    public function hashID()
    {
        return $this->getRouteKey();
    }
}
