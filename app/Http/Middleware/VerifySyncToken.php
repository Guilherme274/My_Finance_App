<?php

namespace App\Http\Middleware;

use App\Services\Sync\SyncSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protege a API de sincronização do hub com o token compartilhado.
 */
class VerifySyncToken
{
    public function __construct(private readonly SyncSettings $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (config('finance.role') !== 'hub') {
            abort(404);
        }

        $provided = (string) $request->bearerToken();

        if ($provided === '' || ! hash_equals($this->settings->hubToken(), $provided)) {
            return response()->json(['message' => 'Token de sincronização inválido.'], 401);
        }

        return $next($request);
    }
}
