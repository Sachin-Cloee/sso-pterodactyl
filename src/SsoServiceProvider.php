<?php

namespace WemX\Sso;

use Illuminate\Support\ServiceProvider;
use WemX\Sso\Commands\GenerateSecretKey;

class SsoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([
            GenerateSecretKey::class,
        ]);

        $this->publishes([
            __DIR__ . '/config/sso-wemx.php' => config_path('sso-wemx.php'),
        ], 'sso-wemx');

        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/sso-wemx.php',
            'sso-wemx'
        );
    }
}
