<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Request;

class HelpersFun extends ServiceProvider
{
    public function boot()
    {
        View::composer('*', function ($view) {
            $view->with('open', function (...$toggle) {
                foreach ($toggle as $k => $v) {
                    if ($v == Request::path()) {
                        return ' nav-item-open';
                    }
                }
            });

            $view->with('active', function ($v) {
                return $v == Request::path() ? ' active' : '';
            });

            $view->with('activeSidebar', function ($v) {
                return $v == Request::path() ? ' active-sidebar' : '';
            });
        });
    }
}