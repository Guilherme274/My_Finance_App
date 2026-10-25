<?php

namespace App\Services\MercadoPago;

use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\Transaction;
use App\Services\CategoryMatcher;
use App\Services\Import\CsvImporter;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * Importa pagamentos aprovados do Mercado Pago como lançamentos na conta
 * "Mercado Pago". Usa o id do pagamento para nunca duplicar.
 */
class MercadoPagoImporter
{
    public function __construct(
        private readonly MercadoPagoClient $client,
        private readonly CsvImporter $importer,
        private readonly CategoryMatcher $matcher,
    ) {}

    public function import(int $days = 30): ImportBatch
    {
        $me = $this->client->me();
        $myId = (string) $me['id'];

        $account = Account::firstOrCreate(
            ['name' => config('finance.mercadopago.account_name')],
            ['type' => 'wallet', 'color' => '#00b1ea'],
        );

        $end = CarbonImmutable::now();
        $begin = $end->subDays($days)->startOfDay();

        $payments = [];
        // Pagamentos recebidos (você é o "collector") e enviados (você é o "payer").
        foreach ([[], ['payer.id' => $myId]] as $filters) {
            foreach ($this->fetchAll($begin, $end, $filters) as $payment) {
                $payments[$payment['id']] = $payment;
            }
        }

        $rows = [];
        foreach ($payments as $payment) {
            if (($payment['status'] ?? null) !== 'approved') {
                continue;
            }
            $rows[] = $this->mapPayment($payment, $myId);
        }

        $existing = Transaction::withTrashed()
            ->where('account_id', $account->id)
            ->whereIn('external_id', array_column($rows, 'external_id'))
            ->pluck('external_id')
            ->flip();

        foreach ($rows as &$row) {
            $row['duplicate'] = $existing->has($row['external_id']);
        }
        unset($row);

        return $this->importer->commit($rows, $account, null, "API Mercado Pago ({$days} dias)", 'mercadopago');
    }

    /**
     * @return array<string, mixed>
     */
    public function mapPayment(array $payment, string $myId): array
    {
        $isIncoming = (string) ($payment['collector_id'] ?? '') === $myId;

        $gross = (float) ($payment['transaction_amount'] ?? 0);
        $net = (float) ($payment['transaction_details']['net_received_amount'] ?? $gross);
        $value = $isIncoming ? $net : ($payment['transaction_details']['total_paid_amount'] ?? $gross);

        $cents = Money::parse((float) $value);
        $description = trim(($payment['description'] ?? '') ?: ($payment['payment_method_id'] ?? 'Mercado Pago'));
        $description = 'MP: '.$description;

        $date = CarbonImmutable::parse($payment['date_approved'] ?? $payment['date_created'])
            ->setTimezone(config('app.timezone'));

        return [
            'date' => $date->toDateString(),
            'description' => mb_substr($description, 0, 250),
            'amount_cents' => $isIncoming ? abs($cents) : -abs($cents),
            'installment' => null,
            'installments' => ($payment['installments'] ?? 1) > 1 ? (int) $payment['installments'] : null,
            'external_id' => 'mp:'.$payment['id'],
            'hash' => sha1('mercadopago|'.$payment['id']),
            'category_id' => $this->matcher->match($description),
        ];
    }

    /**
     * @return \Generator<int, array>
     */
    private function fetchAll(CarbonImmutable $begin, CarbonImmutable $end, array $filters): \Generator
    {
        $offset = 0;
        $limit = 50;

        do {
            $page = $this->client->searchPayments($begin, $end, $offset, $limit, $filters);
            $results = $page['results'] ?? [];
            yield from $results;

            $offset += $limit;
            $total = (int) ($page['paging']['total'] ?? 0);
        } while ($results && $offset < $total && $offset < 1000);
    }
}
