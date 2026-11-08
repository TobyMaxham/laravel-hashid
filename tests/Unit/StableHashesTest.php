<?php

declare(strict_types=1);

use TobyMaxham\HashId\IdHasherManager;

/*
 | Fixed reference hashes. If one of these tests fails, the generated hashes changed
 | (e.g. by an update of hashids/hashids) and all existing links would break.
 | The values were created with hashids/hashids 4.1 and are identical in 5.x.
 */

it('creates the same hashes as before', function (string $salt, int $length, string $alphabet, int $id, string $hash) {
    config()->set('hashids.length', $length);
    config()->set('hashids.alphabet', $alphabet);

    $manager = new IdHasherManager('', null, $salt);

    expect($manager->encodeId($id))->toBe($hash)
        ->and($manager->decodeId($hash))->toBe($id);
})->with([
    ['test-salt', 18, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 0, 'Lm4N8VYxZAJ0XzKDde'],
    ['test-salt', 18, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 1, 'LmgNadY3kK3MWz5QkA'],
    ['test-salt', 18, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 34, '40Azbm1xDZ3VoveOaE'],
    ['test-salt', 18, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 400, 'Lm4N8VYxZ4q30XzKDd'],
    ['test-salt', 18, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 999999, 'LMj08e3yA8eA37wlRz'],
    ['test-salt', 18, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', PHP_INT_MAX, '1PxG5adPgwZQVrw53X'],
    ['', 0, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 0, 'gY'],
    ['', 0, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 1, 'jR'],
    ['', 0, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 34, '1R'],
    ['', 0, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 400, 'gxZ'],
    ['', 0, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', 999999, 'wR3wR'],
    ['', 0, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890', PHP_INT_MAX, 'p21ZD04m8GQ42'],
    ['x|users', 10, 'abcdefghijklmnop', 0, 'opgnblmbjk'],
    ['x|users', 10, 'abcdefghijklmnop', 1, 'emanbkjblo'],
    ['x|users', 10, 'abcdefghijklmnop', 34, 'manbkejblo'],
    ['x|users', 10, 'abcdefghijklmnop', 400, 'pgnblaajbj'],
    ['x|users', 10, 'abcdefghijklmnop', 999999, 'nblplaaemb'],
    ['x|users', 10, 'abcdefghijklmnop', PHP_INT_MAX, 'dmndokkdagjdmpjekaen'],
]);

it('creates the same model hashes as before', function () {
    // per_model: salt + "|" + table name. Changing this format changes all model hashes.
    config()->set('hashids.length', 10);
    config()->set('hashids.alphabet', 'abcdefghijklmnop');

    expect((new IdHasherManager('', null, 'x'))->for('users')->encodeId(400))
        ->toBe((new IdHasherManager('', null, 'x|users'))->encodeId(400));
});
