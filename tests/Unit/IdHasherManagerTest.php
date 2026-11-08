<?php

declare(strict_types=1);

use Hashids\Hashids;
use TobyMaxham\HashId\IdHasherManager;
use TobyMaxham\HashId\Exceptions\WrongIdException;
use TobyMaxham\HashId\Tests\Fixtures\CustomException;

function manager(?string $prefix = null, ?string $mode = null): IdHasherManager
{
    $manager = new IdHasherManager($prefix, $mode);
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

describe('prefix', function () {
    it('treats a null prefix from the config as no prefix', function () {
        config()->set('hashids.prefix', null);
        $manager = manager();

        expect($manager->prefix())->toBe('')
            ->and($manager->decodeId($manager->encodeId(34)))->toBe(34);
    });

    it('reads an empty prefix from the env', function (string $value) {
        $_ENV['HASH_PREFIX'] = $_SERVER['HASH_PREFIX'] = $value;

        try {
            $config = require __DIR__.'/../../config/hashids.php';
        } finally {
            unset($_ENV['HASH_PREFIX'], $_SERVER['HASH_PREFIX']);
        }

        expect((new IdHasherManager($config['prefix'] ?? ''))->prefix())->toBe('');
    })->with(['empty' => '', 'null' => 'null', '(empty)' => '(empty)']);

    it('encodes without prefix on request', function () {
        expect(manager()->encodeIdWithoutPrefix(34))->toBe(substr(manager()->encodeId(34), 2));
    });
});

describe('prefix_mode', function () {
    it('defaults to strict', function () {
        expect(manager()->prefixMode())->toBe(IdHasherManager::PREFIX_STRICT);
    });

    it('reads the mode from the config', function () {
        config()->set('hashids.prefix_mode', 'optional');

        expect(manager()->prefixMode())->toBe(IdHasherManager::PREFIX_OPTIONAL);
    });

    it('falls back to strict when the config value is missing', function () {
        config()->set('hashids.prefix_mode', null);

        expect(manager()->prefixMode())->toBe(IdHasherManager::PREFIX_STRICT);
    });

    it('rejects an unknown mode', function () {
        manager(null, 'sometimes');
    })->throws(InvalidArgumentException::class, 'Invalid hashids.prefix_mode [sometimes]');

    it('accepts hashes with and without prefix in optional mode', function () {
        $manager = manager(null, 'optional');

        expect($manager->decodeId($manager->encodeId(34)))->toBe(34)
            ->and($manager->decodeId($manager->encodeIdWithoutPrefix(34)))->toBe(34);
    });

    it('still encodes with prefix in optional mode', function () {
        expect(manager(null, 'optional')->encodeId(34))->toStartWith('h:');
    });

    it('still rejects invalid hashes in optional mode', function (string $hash) {
        manager(null, 'optional')->decodeId($hash);
    })->with(['h:invalid', 'invalid', '34', 'x:abc', 'h:'])->throws(WrongIdException::class);

    it('handles a prefix that is also a valid hash character', function () {
        // Worst case: the prefix is the first character of a real hash without prefix.
        $raw = manager('')->encodeId(1);
        $manager = manager($raw[0], 'optional');

        expect($manager->decodeId($raw))->toBe(1)
            ->and($manager->decodeId($manager->encodeId(1)))->toBe(1)
            ->and($manager->decodeId($manager->encodeId(2)))->toBe(2);
    });

    it('ignores the mode when there is no prefix', function () {
        $manager = manager('', 'optional');

        expect($manager->decodeId($manager->encodeId(34)))->toBe(34);
    });
});

describe('hardening', function () {
    it('rejects a hash that contains several numbers', function () {
        $hashids = new Hashids('test-salt', 18, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

        manager()->decodeId('h:'.$hashids->encode(1, 2));
    })->throws(WrongIdException::class);

    it('rejects numbers larger than PHP_INT_MAX', function () {
        $hashids = new Hashids('test-salt', 18, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890');

        expect(manager()->decodeId('h:'.$hashids->encode('99999999999999999999999'), false))->toBeNull();
    });

    it('rejects an empty value', function () {
        expect(manager()->decodeId('', false))->toBeNull()
            ->and(manager('')->decodeId('', false))->toBeNull()
            ->and(manager()->decodeId(null, false))->toBeNull();
    });
});
