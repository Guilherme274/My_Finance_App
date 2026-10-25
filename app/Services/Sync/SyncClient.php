<?php

namespace App\Services\Sync;

use App\Models\Setting;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente de sincronização usado no celular: tenta cada URL do hub
 * (ex.: IP do Wi-Fi e depois IP do Tailscale), envia as alterações locais
 * e baixa as alterações do PC.
 */
class SyncClient
{
    public function __construct(
        private readonly SyncService $sync,
        private readonly SyncSettings $settings,
    ) {}

    /**
     * @return array{url: string, pushed: array, pulled: array}
     */
    public function sync(): array
    {
        $token = $this->settings->clientToken();
        if (! $token) {
            throw new RuntimeException('Informe o token de sincronização exibido no PC.');
        }

        $url = $this->findReachableHub($token);

        // 1) PUSH: envia alterações locais desde o último envio (relógio local).
        $pushStartedAt = $this->sync->now();
        $local = $this->sync->changesSince(Setting::get('sync_last_push_at'));

        $pushResponse = $this->http($token)->post($url.'/api/sync/push', ['changes' => $local]);
        $pushResponse->throw();
        Setting::put('sync_last_push_at', $pushStartedAt);

        // 2) PULL: recebe alterações do PC desde o último recebimento (relógio do PC).
        $pullResponse = $this->http($token)->get($url.'/api/sync/pull', [
            'since' => Setting::get('sync_last_pull_at'),
        ]);
        $pullResponse->throw();

        $pulled = $this->sync->apply($pullResponse->json('changes', []));
        Setting::put('sync_last_pull_at', $pullResponse->json('server_time'));
        Setting::put('sync_last_success_at', now()->toDateTimeString());
        Setting::put('sync_last_url', $url);

        return [
            'url' => $url,
            'pushed' => $pushResponse->json('stats', []),
            'pulled' => $pulled,
        ];
    }

    /**
     * Retorna a primeira URL que responde ao /api/sync/ping com o token válido.
     */
    public function findReachableHub(string $token): string
    {
        $urls = $this->settings->hubUrls();
        if (! $urls) {
            throw new RuntimeException('Cadastre ao menos um endereço do PC (Wi-Fi e/ou Tailscale).');
        }

        $errors = [];
        foreach ($urls as $url) {
            try {
                $response = $this->http($token, 3)->get($url.'/api/sync/ping');
                if ($response->ok() && $response->json('app') === 'gestao-financeira') {
                    return $url;
                }
                $errors[] = "{$url}: HTTP {$response->status()}";
            } catch (ConnectionException $e) {
                $errors[] = "{$url}: sem conexão";
            }
        }

        throw new RuntimeException('Não foi possível alcançar o PC. '.implode(' | ', $errors));
    }

    private function http(string $token, ?int $timeout = null)
    {
        return Http::withToken($token)
            ->acceptJson()
            ->timeout($timeout ?? config('finance.sync.timeout'));
    }
}
