<?php

declare(strict_types=1);

namespace PavelZanek\LaravelDeepl\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Orchestra\Testbench\TestCase;
use PavelZanek\LaravelDeepl\LaravelDeeplServiceProvider;

class OrchestraTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('laravel-deepl.api_key', 'test_auth_key');

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('laravel-deepl.api_key', 'test_auth_key');
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDeeplServiceProvider::class,
        ];
    }
}
