<?php

declare(strict_types=1);

namespace TobyMaxham\HashId;

use Illuminate\Support\ServiceProvider;

class HashIdServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/hashids.php', 'hashids');

        // Singleton, so the hashers per model are only created once.
        $this->app->singleton(IdHasherManager::class, fn () => new IdHasherManager);

        $this->app->alias(IdHasherManager::class, 'idhasher');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/hashids.php' => config_path('hashids.php'),
        ], 'hashid-config');
    }
}
