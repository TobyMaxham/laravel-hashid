<?php

namespace TobyMaxham\HashId;

use Hashids\Hashids;
use Illuminate\Support\ServiceProvider;

class HashIdServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/hashids.php' => config_path('hashids.php'),
        ]);

        $this->mergeConfigFrom(
            __DIR__.'/../config/hashids.php', 'hashids'
        );
    }

    public function register()
    {
        $this->app->bind(IdHasherManager::class, function () {
            $instance = new IdHasherManager;
            $instance->setHasher(new Hashids(
                config('hashids.salt'),
                config('hashids.length'),
                config('hashids.alphabet')
            ));

            return $instance;
        });

        $this->app->alias(IdHasherManager::class, 'idhasher');
    }
}
