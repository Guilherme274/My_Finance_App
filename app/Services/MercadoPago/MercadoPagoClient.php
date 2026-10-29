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
     * Busca um pagamento específico pelo ID (funciona mesmo para pagamentos com mais de 12 meses).
     */
    public function getPayment(string $id): ?array
    {
        $response = $this->request()->get('/v1/payments/'.rawurlencode($id));
        if (! $response->successful()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Busca pagamentos (recebidos ou enviados) com ou sem filtro de intervalo de datas.
     *
     * @param  array<string, scalar>  $filters  filtros extras (ex.: ['payer.id' => 123])
     * @return array{results: array<int, array>, paging: array}
     */
    public function searchPayments(?CarbonInterface $begin = null, ?CarbonInterface $end = null, int $offset = 0, int $limit = 50, array $filters = []): array
    {
        $query = [
            'sort' => 'date_created',
            'criteria' => 'desc',
            'offset' => $offset,
            'limit' => $limit,
        ];

        if ($begin && $end) {
            $query['range'] = 'date_created';
            $query['begin_date'] = $begin->format('Y-m-d\TH:i:s.vP');
            $query['end_date'] = $end->format('Y-m-d\TH:i:s.vP');
        }

        return $this->request()->get('/v1/payments/search', array_merge($query, $filters))->throw()->json();
    }

    /**
     * Solicita a geração do relatório de liberações/movimentações da conta (máx. 90 dias por bloco).
     */
    public function createReleaseReport(CarbonInterface $begin, CarbonInterface $end): array
    {
        $response = $this->request()->post('/v1/account/release_report', [
            'begin_date' => $begin->utc()->format('Y-m-d\TH:i:s\Z'),
            'end_date' => $end->utc()->format('Y-m-d\TH:i:s\Z'),
        ]);

        return $response->json() ?? [];
    }

    /**
     * Lista os relatórios de liberações/movimentações já gerados na conta.
     */
    public function listReleaseReports(): array
    {
        $response = $this->request()->get('/v1/account/release_report/list');
        if (! $response->successful()) {
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * Baixa um relatório de liberações/movimentações gerado (conteúdo CSV separado por ponto-e-vírgula).
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
