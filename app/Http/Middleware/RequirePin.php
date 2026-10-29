<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige um PIN (APP_PIN) para usar o sistema pelo navegador.
 * Importante no PC, que fica acessível na rede Wi-Fi e no Tailscale.
 */
class RequirePin
{
    public function handle(Request $request, Closure $next): Response
    {
        $hasPin = Setting::hasPin();
        $biometricsEnabled = filter_var(Setting::get('settings_biometrics_enabled', false), FILTER_VALIDATE_BOOLEAN);

        if ($request->routeIs('pin.*')) {
            return $next($request);
        }

        if ($biometricsEnabled && $request->session()->get('pin_ok') !== true) {
            return redirect()->guest(route('pin.show'));
        }

        if (! $hasPin || $request->session()->get('pin_ok') === true) {
            return $next($request);
        }

        return redirect()->route('pin.show');
    }
}
