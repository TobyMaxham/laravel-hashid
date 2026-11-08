<?php

namespace TobyMaxham\HashId\Tests;

use Illuminate\Support\Facades\Schema;
use TobyMaxham\HashId\Facades\IdHasher;
use Illuminate\Database\Schema\Blueprint;
use TobyMaxham\HashId\HashIdServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [HashIdServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('hashids.salt', 'test-salt');
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->softDeletes();
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id');
            $table->string('body');
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
        });
    }

    /**
     * Change the hashids config and drop cached hasher instances,
     * so the next resolve uses the new values.
     */
    protected function setHashConfig(array $config): void
    {
        foreach ($config as $key => $value) {
            config()->set('hashids.'.$key, $value);
        }

        IdHasher::clearResolvedInstances();
    }
}
