<?php

declare(strict_types=1);

namespace TobyMaxham\HashId;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\Console\DecodeCommand;
use TobyMaxham\HashId\Console\EncodeCommand;
use Illuminate\Foundation\Console\AboutCommand;

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

        if ($this->app->runningInConsole()) {
            $this->commands([EncodeCommand::class, DecodeCommand::class]);
            $this->registerAboutCommand();
        }

        $this->registerRequestMacros();
    }

    /**
     * Shows the config in "php artisan about", incl. a warning for the default salt.
     */
    protected function registerAboutCommand(): void
    {
        AboutCommand::add('Laravel HashID', function () {
            $manager = $this->app->make(IdHasherManager::class);

            return [
                'Salt' => $manager->usesDefaultSalt()
                    ? '<fg=red;options=bold>DEFAULT (insecure, set HASH_SALT)</>'
                    : '<fg=green;options=bold>CUSTOM</>',
                'Previous salts' => (string) count($manager->fallbackSalts()),
                'Prefix'         => '' === $manager->prefix() ? '<fg=yellow>none</>' : $manager->prefix(),
                'Prefix mode'    => $manager->prefixMode(),
                'Per model'      => AboutCommand::format(config('hashids.per_model', true), console: fn ($value) => $value ? '<fg=green;options=bold>ENABLED</>' : '<fg=yellow;options=bold>OFF</>'),
                'Legacy hashes'  => AboutCommand::format(config('hashids.accept_legacy_hashes', false), console: fn ($value) => $value ? '<fg=yellow;options=bold>ACCEPTED</>' : 'OFF'),
            ];
        });
    }

    /**
     * $request->hashId('product_id', Product::class): ?int
     * $request->hashIds('product_ids', Product::class): list<int>
     */
    protected function registerRequestMacros(): void
    {
        Request::macro('hashId', function (string $key, Model|string|null $model = null): ?int {
            /** @var Request $this */
            $value = $this->input($key);

            if (! is_string($value) && ! is_int($value)) {
                return null;
            }

            return (null === $model ? IdHasher::getFacadeRoot() : IdHasher::for($model))->decodeId($value, false);
        });

        Request::macro('hashIds', function (string $key, Model|string|null $model = null): array {
            /** @var Request $this */
            $hasher = null === $model ? IdHasher::getFacadeRoot() : IdHasher::for($model);
            $ids = [];

            foreach ((array) $this->input($key, []) as $value) {
                if ((is_string($value) || is_int($value)) && null !== $id = $hasher->decodeId($value, false)) {
                    $ids[] = $id;
                }
            }

            return array_values(array_unique($ids));
        });
    }
}
