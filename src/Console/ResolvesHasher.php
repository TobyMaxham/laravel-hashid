<?php

declare(strict_types=1);

namespace TobyMaxham\HashId\Console;

use TobyMaxham\HashId\IdHasherManager;

trait ResolvesHasher
{
    protected function hasher(IdHasherManager $manager): ?IdHasherManager
    {
        $model = $this->option('model');

        if (null === $model || '' === $model) {
            return $manager;
        }

        if (str_contains($model, '\\') && ! class_exists($model)) {
            $this->error(sprintf('Model class [%s] does not exist.', $model));

            return null;
        }

        return $manager->for($model);
    }
}
