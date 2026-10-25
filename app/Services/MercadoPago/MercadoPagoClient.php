<?php

namespace App\Services\MercadoPago;

use Carbon\CarbonInterface;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente mínimo da API do Mercado Pago (https://api.mercadopago.com).
 *
 * Autenticação: Access Token de produção da sua aplicação, obtido em
 * https://www.mercadopago.com.br/developers/panel/app  ->  Credenciais de produção.
 * O token fica APENAS no .env do PC (MERCADOPAGO_ACCESS_TOKEN).
 */
class MercadoPagoClient
{
    public function isConfigured(): bool
    {
        return filled(config('finance.mercadopago.access_token'));
    }

    /**
     * Dados da conta dona do token (id, nickname, e-mail...).
     */
    public function me(): array
    {
        return $this->request()->get('/users/me')->throw()->json();
    }

    /**
     * Busca pagamentos (recebidos ou enviados) em um intervalo de datas.
     *
     * @param  array<string, scalar>  $filters  filtros extras (ex.: ['payer.id' => 123])
     * @return array{results: array<int, array>, paging: array}
     */
    public function searchPayments(CarbonInterface $begin, CarbonInterface $end, int $offset = 0, int $limit = 50, array $filters = []): array
    {
        return $this->request()->get('/v1/payments/search', array_merge([
            'sort' => 'date_created',
            'criteria' => 'desc',
            'range' => 'date_created',
            'begin_date' => $begin->toIso8601String(),
            'end_date' => $end->toIso8601String(),
            'offset' => $offset,
            'limit' => $limit,
        ], $filters))->throw()->json();
    }

    /**
     * Solicita a geração do relatório de liberações (extrato consolidado, assíncrono).
     */
    public function createReleaseReport(CarbonInterface $begin, CarbonInterface $end): array
    {
        return $this->request()->post('/v1/account/release_report', [
            'begin_date' => $begin->utc()->format('Y-m-d\TH:i:s\Z'),
            'end_date' => $end->utc()->format('Y-m-d\TH:i:s\Z'),
        ])->throw()->json() ?? [];
    }

    /**
     * Lista os relatórios de liberações já gerados.
     */
    public function listReleaseReports(): array
    {
        return $this->request()->get('/v1/account/release_report/list')->throw()->json() ?? [];
    }

    /**
     * Baixa um relatório gerado (conteúdo CSV).
     */
    public function downloadReleaseReport(string $fileName): string
    {
        return $this->request()->accept('text/csv')
            ->get('/v1/account/release_report/'.rawurlencode($fileName))
            ->throw()
            ->body();
    }

    private function request(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Configure MERCADOPAGO_ACCESS_TOKEN no arquivo .env do PC.');
        }

        return Http::baseUrl(config('finance.mercadopago.base_url'))
            ->withToken(config('finance.mercadopago.access_token'))
            ->acceptJson()
            ->timeout(config('finance.mercadopago.timeout'))
            ->retry(2, 500, throw: false);
    }
}
