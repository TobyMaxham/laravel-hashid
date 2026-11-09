<?php

declare(strict_types=1);

const BOOST_PATH = __DIR__.'/../../resources/boost';

it('has the boost guideline', function () {
    expect(BOOST_PATH.'/guidelines/core.blade.php')->toBeReadableFile();
});

it('has valid skill frontmatter', function (string $skillFile) {
    preg_match('/\A---\n(.*?)\n---\n/s', (string) file_get_contents($skillFile), $matches);

    expect($matches)->not->toBeEmpty('Missing frontmatter in '.$skillFile);

    preg_match('/^name: (.+)$/m', $matches[1], $name);
    preg_match('/^description: (.+)$/m', $matches[1], $description);

    expect($name[1] ?? null)->toBe(basename(dirname($skillFile)))
        ->and(strlen($description[1] ?? ''))->toBeGreaterThan(0)->toBeLessThanOrEqual(1024);
})->with(fn () => glob(BOOST_PATH.'/skills/*/SKILL.md'));

it('only links to existing files in skills', function (string $file) {
    preg_match_all('/\]\((?!https?:)([^)#]+)(?:#[^)]*)?\)/', (string) file_get_contents($file), $links);

    foreach ($links[1] as $link) {
        expect(dirname($file).'/'.$link)->toBeReadableFile();
    }
})->with(fn () => [
    ...glob(BOOST_PATH.'/skills/*/SKILL.md'),
    ...glob(BOOST_PATH.'/skills/*/references/*.md'),
]);
