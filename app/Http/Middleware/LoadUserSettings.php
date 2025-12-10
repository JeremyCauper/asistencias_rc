<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Controllers\SettingsController;

class LoadUserSettings
{
    public function handle($request, Closure $next)
    {
        $settings = SettingsController::get();

        if ($settings) {
            config([
                'ajustes.customModulos' => (object) $settings['customModulos'] ?? [],
                'ajustes.tipo_sistema' => $settings['tipo_sistema'] ?? 0,
                'ajustes.config' => (object) $settings['config'] ?? [],
                'ajustes.rutaRedirect' => $settings['rutaRedirect'] ?? '/',
            ]);
        }

        return $next($request);
    }
}
