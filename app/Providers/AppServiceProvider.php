<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Compartimos la configuración como una variable $front
        View::share('ft_version', config('front_assets.version'));
        View::share('ft_css', config('front_assets.css'));
        View::share('ft_js', config('front_assets.js'));
        View::share('ft_json', config('front_assets.json'));
        View::share('ft_img', config('front_assets.img'));

        View::composer('*', function ($view) {
            $view->with('customModulos', config('ajustes.customModulos'));
            $view->with('tipo_sistema', config('ajustes.tipo_sistema'));
            $view->with('config', config('ajustes.config'));
            $view->with('rutaRedirect', config('ajustes.rutaRedirect'));
        });
    }
}
