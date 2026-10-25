<?php

namespace App\Services\Sync;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Configurações de sincronização guardadas na tabela local `settings`.
 */
class SyncSettings
{
    /**
     * Token aceito pelo hub (PC). Usa SYNC_TOKEN do .env ou gera um automaticamente.
     */
    public function hubToken(): string
    {
        if ($token = config('finance.sync.token')) {
            return $token;
        }

        $token = Setting::get('hub_sync_token');
        if (! $token) {
            $token = Str::random(40);
            Setting::put('hub_sync_token', $token);
        }

        return $token;
    }

    public function regenerateHubToken(): string
    {
        $token = Str::random(40);
        Setting::put('hub_sync_token', $token);

        return $token;
    }

    /**
     * URLs do hub configuradas no celular (Wi-Fi primeiro, Tailscale depois).
     *
     * @return array<int, string>
     */
    public function hubUrls(): array
    {
        return collect(preg_split('/[\s,;]+/', (string) Setting::get('sync_hub_urls', '')))
            ->map(fn ($u) => rtrim(trim($u), '/'))
            ->filter()
            ->map(fn ($u) => Str::startsWith($u, ['http://', 'https://']) ? $u : 'http://'.$u)
            ->values()
            ->all();
    }

    public function clientToken(): ?string
    {
        return Setting::get('sync_token');
    }

    /**
     * Endereços IPv4 do PC para exibir na tela de pareamento.
     * IPs 100.64.0.0/10 são da rede Tailscale.
     *
     * @return array<int, array{ip: string, type: string}>
     */
    public function localAddresses(): array
    {
        $ips = gethostbynamel(gethostname()) ?: [];

        return collect($ips)
            ->reject(fn ($ip) => str_starts_with($ip, '127.'))
            ->unique()
            ->map(function ($ip) {
                $long = ip2long($ip);
                $isTailscale = $long >= ip2long('100.64.0.0') && $long <= ip2long('100.127.255.255');

                return ['ip' => $ip, 'type' => $isTailscale ? 'Tailscale' : 'Wi-Fi / rede local'];
            })
            ->sortBy(fn ($a) => $a['type'] === 'Tailscale' ? 1 : 0)
            ->values()
            ->all();
    }
}
