<?php

declare(strict_types=1);

use TobyMaxham\HashId\Hasher;
use Illuminate\Support\Facades\Log;
use TobyMaxham\HashId\IdHasherManager;
use Illuminate\Database\Eloquent\Model;

beforeEach(fn () => IdHasherManager::resetDefaultSaltWarning());

describe('prefix', function () {
    it('rejects characters that are not url safe', function (string $prefix) {
        expect(fn () => new IdHasherManager($prefix))
            ->toThrow(InvalidArgumentException::class, 'Invalid hashids.prefix ['.$prefix.']. Only letters (a-z, A-Z), digits and "-", ".", "_", "~", ":" are allowed');
    })->with([
        // break the route
        'h/', '?h', 'h#', 'h%', 'h ', "h\t",
        // need encoding in urls
        'h<', 'h>', 'h"', 'h\\', 'h{', 'h}', 'h|', 'h^', 'h`', 'h[', 'h]', "h'",
        // break query strings or forms
        'h+', 'h&', 'h=', 'h;', 'h,', 'h@', 'h!', 'h$', 'h*', 'h(', 'h)',
        // not ascii
        'ä:', 'h€', "h\u{200B}",
        // a trailing new line must not pass the check
        "h\n",
    ]);

    it('accepts url safe prefixes', function (string $prefix) {
        expect((new IdHasherManager($prefix))->prefix())->toBe($prefix);
    })->with(['h:', 'usr_', 'p-', 'h.', 'h~', 'A1:', 'user_v2-', '']);

    it('also checks model prefixes', function () {
        $model = new class extends Model
        {
            use Hasher;

            public const HASH_ID_PREFIX = 'bad/';
        };

        app(IdHasherManager::class)->for($model);
    })->throws(InvalidArgumentException::class, 'Invalid hashids.prefix [bad/]');
});

describe('length', function () {
    it('rejects invalid lengths', function (mixed $length) {
        config()->set('hashids.length', $length);

        (new IdHasherManager)->encodeId(1);
    })->with([-1, '-5', 'abc', 1.5, '1.5'])->throws(InvalidArgumentException::class, 'Invalid hashids.length');

    it('accepts integers and numeric strings', function (mixed $length) {
        config()->set('hashids.length', $length);

        expect(strlen((new IdHasherManager(''))->encodeId(1)))->toBeGreaterThanOrEqual((int) $length);
    })->with([0, 10, '12']);
});

describe('alphabet', function () {
    it('rejects invalid alphabets with the config key in the message', function (string $alphabet, string $message) {
        config()->set('hashids.alphabet', $alphabet);

        expect(fn () => (new IdHasherManager)->encodeId(1))
            ->toThrow(InvalidArgumentException::class, 'Invalid hashids.alphabet ['.$alphabet.']: '.$message);
    })->with([
        ['abc', 'The Hashids alphabet must contain at least 16 unique characters.'],
        ['abcdefghijklmnop qrs', "The Hashids alphabet can't contain spaces."],
    ]);
});

describe('default salt', function () {
    it('detects the default and an empty salt', function (string $salt, bool $default) {
        expect((new IdHasherManager(null, null, $salt))->usesDefaultSalt())->toBe($default);
    })->with([
        ['your-salt-string', true],
        ['', true],
        ['my-secret', false],
    ]);

    it('logs a warning once in production', function () {
        app()['env'] = 'production';
        Log::spy();

        (new IdHasherManager(null, null, 'your-salt-string'))->encodeId(1);
        (new IdHasherManager(null, null, 'your-salt-string'))->encodeId(2);

        Log::shouldHaveReceived('warning')->once()->withArgs(fn ($message) => str_contains($message, 'default salt'));
    });

    it('does not log outside of production', function () {
        Log::spy();

        (new IdHasherManager(null, null, 'your-salt-string'))->encodeId(1);

        Log::shouldNotHaveReceived('warning');
    });

    it('does not log for a custom salt', function () {
        app()['env'] = 'production';
        Log::spy();

        (new IdHasherManager(null, null, 'my-secret'))->encodeId(1);

        Log::shouldNotHaveReceived('warning');
    });
});

describe('about command', function () {
    it('shows the config and warns about the default salt', function () {
        $this->setHashConfig(['salt' => 'your-salt-string']);

        $this->artisan('about', ['--only' => 'laravel hashid'])
            ->expectsOutputToContain('DEFAULT (insecure, set HASH_SALT)')
            ->expectsOutputToContain('strict')
            ->assertSuccessful();
    });

    it('shows a custom salt and the other options', function () {
        $this->setHashConfig(['salt' => 'secret', 'prefix' => '', 'per_model' => false, 'accept_legacy_hashes' => true, 'previous_salts' => 'a,b']);

        $this->artisan('about', ['--only' => 'laravel hashid', '--json' => true])
            ->expectsOutputToContain('"salt":"CUSTOM","previous_salts":"2","prefix":"none","prefix_mode":"strict","per_model":false,"legacy_hashes":true')
            ->assertSuccessful();

        $this->artisan('about', ['--only' => 'laravel hashid'])
            ->expectsOutputToContain('CUSTOM')
            ->expectsOutputToContain('none')
            ->expectsOutputToContain('OFF')
            ->expectsOutputToContain('ACCEPTED')
            ->assertSuccessful();
    });
});

describe('exception', function () {
    it('rejects a class that is not an exception', function (mixed $class, string $shown) {
        config()->set('hashids.exception', $class);

        expect(fn () => (new IdHasherManager)->decodeId('h:invalid'))
            ->toThrow(InvalidArgumentException::class, 'Invalid hashids.exception ['.$shown.']');
    })->with([
        'no exception'  => [stdClass::class, 'stdClass'],
        'missing class' => ['App\\Missing', 'App\\Missing'],
        'not a string'  => [123, 'int'],
    ]);
});
