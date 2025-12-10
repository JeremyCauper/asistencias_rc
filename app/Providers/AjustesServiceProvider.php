<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AjustesServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Singleton que almacenará ajustes por request
        $this->app->singleton('ajustes.cache', function () {
            return [];
        });
    }

    public function boot()
    {
        //
    }
}