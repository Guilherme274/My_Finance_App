<?php

namespace App\Services\Pluggy;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente da API oficial da Pluggy Open Finance (https://api.pluggy.ai).
 *
 * Permite gerar o Connect Token para o widget do Pluggy Connect (Nubank e demais
 * bancos via Open Finance Brasil) e sincronizar contas, saldos, transações de
 * conta/cartão e investimentos.
 */
class PluggyClient
{
    public function isConfigured(): bool
    {
        return filled(config('finance.pluggy.client_id'))
            && filled(config('finance.pluggy.client_secret'));
    }

    /**
     * Autentica com clientId + clientSecret em POST /auth e retorna a X-API-KEY (válida por 2h).
     */
    public function apiKey(bool $forceRefresh = false): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Configure PLUGGY_CLIENT_ID e PLUGGY_CLIENT_SECRET no arquivo .env do PC.');
        }

        if ($forceRefresh) {
            Cache::forget('pluggy_api_key');
        }

        return Cache::remember('pluggy_api_key', now()->addMinutes(100), function () {
            $response = Http::baseUrl(config('finance.pluggy.base_url'))
                ->acceptJson()
                ->timeout(config('finance.pluggy.timeout', 25))
                ->post('/auth', [
                    'clientId' => config('finance.pluggy.client_id'),
                    'clientSecret' => config('finance.pluggy.client_secret'),
                ])
                ->throw()
                ->json();

            return (string) ($response['apiKey'] ?? '');
        });
    }

    /**
     * Gera um Connect Token (accessToken) em POST /connect_token para abrir o widget Pluggy Connect.
     */
    public function createConnectToken(?string $itemId = null, string $clientUserId = 'gestao-financeira'): string
    {
        $payload = [
            'options' => [
                'clientUserId' => $clientUserId,
            ],
        ];

        if ($itemId) {
            $payload['itemId'] = $itemId;
        }

        $response = $this->request()
            ->post('/connect_token', $payload)
            ->throw()
            ->json();

        return (string) ($response['accessToken'] ?? '');
    }

    /**
     * Consulta detalhes de uma conexão (Item) em GET /items/{id}.
     */
    public function getItem(string $itemId): array
    {
        return $this->request()->get("/items/{$itemId}")->throw()->json() ?? [];
    }

    /**
     * Lista as contas (corrente + cartão de crédito) vinculadas a um Item em GET /accounts?itemId={itemId}.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAccounts(string $itemId): array
    {
        $res = $this->request()->get('/accounts', ['itemId' => $itemId])->throw()->json();

        return $res['results'] ?? [];
    }

    /**
     * Lista as transações de uma conta em um intervalo de datas em GET /transactions.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getTransactions(string $pluggyAccountId, CarbonInterface $from, CarbonInterface $to): array
    {
        $all = [];
        $page = 1;
        $totalPages = 1;

        do {
            $res = $this->request()->get('/transactions', [
                'accountId' => $pluggyAccountId,
                'from' => $from->format('Y-m-d'),
                'to' => $to->format('Y-m-d'),
                'pageSize' => 100,
                'page' => $page,
            ])->throw()->json();

            foreach ($res['results'] ?? [] as $tx) {
                $all[] = $tx;
            }

            $totalPages = (int) ($res['totalPages'] ?? 1);
            $page++;
        } while ($page <= $totalPages && $page <= 10);

        return $all;
    }

    /**
     * Lista os investimentos (Caixinhas, RDB, CDB, Tesouro, FIIs) vinculados a um Item em GET /investments.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getInvestments(string $itemId): array
    {
        $res = $this->request()->get('/investments', ['itemId' => $itemId])->throw()->json();

        return $res['results'] ?? [];
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(config('finance.pluggy.base_url'))
            ->withHeaders(['X-API-KEY' => $this->apiKey()])
            ->acceptJson()
            ->timeout(config('finance.pluggy.timeout', 25));
    }
}
