<?php

namespace TobyMaxham\HashId;

use Hashids\Hashids;
use RuntimeException;
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

    /**
     * @throws RuntimeException
     */
    public function register()
    {
        if (! \extension_loaded('gmp') && ! \extension_loaded('bcmath')) {
            throw new RuntimeException(
                'Missing required PHP extension: either GMP or BCMath must be installed for hashids/hashids to work.'
            );
        }

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
