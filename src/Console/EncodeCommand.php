<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Console;

use Illuminate\Console\Command;
use TobyMaxham\HashId\IdHasherManager;

class EncodeCommand extends Command
{
    use ResolvesHasher;

    protected $signature = 'hashid:encode
        {id* : One or more ids}
        {--model= : Model class (App\\Models\\User) or key (users). Without it the global salt is used}';

    protected $description = 'Encode ids to hashes';

    public function handle(IdHasherManager $manager): int
    {
        if (null === $hasher = $this->hasher($manager)) {
            return self::FAILURE;
        }

        $rows = [];
        $failed = false;

        foreach ((array) $this->argument('id') as $id) {
            $id = (string) $id;
            $valid = ctype_digit($id);
            $failed = $failed || ! $valid;
            $rows[] = [$id, $valid ? $hasher->encodeId($id) : '<error>not an integer</error>'];
        }

        $this->table(['ID', 'Hash'], $rows);

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
