<?php

use Hashids\Hashids;
use TobyMaxham\HashId\IdHasherManager;
use TobyMaxham\HashId\Exceptions\WrongIdException;
use TobyMaxham\HashId\Tests\Fixtures\CustomException;

function manager(?string $prefix = null): IdHasherManager
{
    $manager = new IdHasherManager($prefix);
    $manager->setHasher(new Hashids('test-salt', 18, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'));

    return $manager;
}

describe('encodeId', function () {
    it('prepends the configured prefix', function () {
        expect(manager()->encodeId(34))->toStartWith('h:');
    });

    it('uses the configured length', function () {
        $hash = manager()->encodeId(34);

        expect(strlen($hash))->toBe(strlen('h:') + 18);
    });

    it('is deterministic', function () {
        expect(manager()->encodeId(34))->toBe(manager()->encodeId(34));
    });

    it('creates different hashes for different ids', function () {
        expect(manager()->encodeId(1))->not->toBe(manager()->encodeId(2));
    });

    it('uses a custom prefix from the constructor', function () {
        expect(manager('p_')->encodeId(34))->toStartWith('p_');
    });

    it('creates no prefix when the prefix is empty', function () {
        $plain = (new Hashids('test-salt', 18, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890'))->encode(34);

        expect(manager('')->encodeId(34))->toBe($plain);
    });

    it('accepts numeric strings', function () {
        expect(manager()->encodeId('34'))->toBe(manager()->encodeId(34));
    });
});

describe('decodeId', function () {
    it('decodes its own hashes', function (int $id) {
        $manager = manager();

        expect($manager->decodeId($manager->encodeId($id)))->toBe($id);
    })->with([1, 34, 400, 999_999, PHP_INT_MAX]);

    it('decodes hashes when the prefix is empty', function () {
        $manager = manager('');

        expect($manager->decodeId($manager->encodeId(34)))->toBe(34);
    });

    it('rejects a hash without the prefix', function () {
        $manager = manager();
        $withoutPrefix = substr($manager->encodeId(34), 2);

        $manager->decodeId($withoutPrefix);
    })->throws(WrongIdException::class);

    it('rejects a hash with a different prefix', function () {
        manager('x:')->decodeId(manager('h:')->encodeId(34));
    })->throws(WrongIdException::class);

    it('rejects a tampered hash', function () {
        $hash = manager()->encodeId(34);

        manager()->decodeId(substr($hash, 0, -1).('a' === $hash[-1] ? 'b' : 'a'));
    })->throws(WrongIdException::class);

    it('rejects hashes from another salt', function () {
        $other = new IdHasherManager;
        $other->setHasher(new Hashids('other-salt', 18));

        manager()->decodeId($other->encodeId(34));
    })->throws(WrongIdException::class);

    it('rejects raw ids', function () {
        manager()->decodeId('34');
    })->throws(WrongIdException::class);

    it('returns null instead of throwing when asked to', function () {
        expect(manager()->decodeId('h:invalid', false))->toBeNull();
    });

    it('throws the configured exception class and message', function () {
        config()->set('hashids.exception', CustomException::class);
        config()->set('hashids.exception_message', 'Nope');

        manager()->decodeId('h:invalid');
    })->throws(CustomException::class, 'Nope');

    it('returns null when exceptions are disabled', function () {
        config()->set('hashids.disable_exception', true);

        expect(manager()->decodeId('h:invalid'))->toBeNull();
    });
});

it('reads the prefix from the config when none is given', function () {
    config()->set('hashids.prefix', 'cfg_');

    expect(manager()->encodeId(34))->toStartWith('cfg_');
});
