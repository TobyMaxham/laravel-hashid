<?php

declare(strict_types=1);

namespace TobyMaxham\HashId;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use TobyMaxham\HashId\Facades\IdHasher;
use TobyMaxham\HashId\Console\DecodeCommand;
use TobyMaxham\HashId\Console\EncodeCommand;

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
        }

        $this->registerRequestMacros();
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
