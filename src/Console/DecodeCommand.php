<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Console;

use Illuminate\Console\Command;
use TobyMaxham\HashId\IdHasherManager;

class DecodeCommand extends Command
{
    use ResolvesHasher;

    protected $signature = 'hashid:decode
        {hash* : One or more hashes}
        {--model= : Model class (App\\Models\\User) or key (users). Without it the global salt is used}';

    protected $description = 'Decode hashes to ids';

    public function handle(IdHasherManager $manager): int
    {
        if (null === $hasher = $this->hasher($manager)) {
            return self::FAILURE;
        }

        $rows = [];
        $failed = false;

        foreach ((array) $this->argument('hash') as $hash) {
            $hash = (string) $hash;
            $id = $hasher->decodeId($hash, false);
            $failed = $failed || null === $id;
            $rows[] = [$hash, $id ?? '<error>invalid</error>'];
        }

        $this->table(['Hash', 'ID'], $rows);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
