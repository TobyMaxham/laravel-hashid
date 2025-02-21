<?php

namespace TobyMaxham\HashId;

use Hashids\Hashids;
use Illuminate\Support\Str;

class IdHasherManager
{

    protected $prefix;

    private Hashids $hasher;

    public function __construct($prefix = null)
    {
        $this->prefix = $prefix ?? config('hashids.prefix');
    }

    public function encodeId($id)
    {
        return $this->prefix.$this->hasher->encode($id);
    }

    public function decodeId($hash, bool $throw = true)
    {
        if (Str::startsWith($hash, $this->prefix)) {
            $result = $this->hasher->decode(Str::after($hash, $this->prefix));

            if (count($result) > 0) {
                return $result[0]; // result is always an array due to quirk in Hashids library
            }
        }

        if($throw && ! config('hashids.disable_exception', false)) {
            $class = config('hashids.exception', WrongIdException::class);

            throw new $class( config('hashids.exception_message', 'WrongIdException') );
        }
    }

    public function setHasher(Hashids $hasher)
    {
        $this->hasher = $hasher;
    }
}
