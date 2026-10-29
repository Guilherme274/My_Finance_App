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
 * Importa TODAS as movimentações reais da conta Mercado Pago:
 * - Combina os Relatórios de Movimentações (/v1/account/release_report) com a API de Pagamentos
 *   (/v1/payments/search e /v1/payments/{id}).
 * - Inclui Pix recebidos, Pix/transferências enviadas (payout), estornos (payout_cancel)
 *   e rendimentos diários do CDI (asset_management).
 * - Desconsidera compras feitas no Mercado Livre com cartão de crédito externo (ex: Nubank)
 *   que não movimentam o saldo da conta Mercado Pago.
 */
class MercadoPagoImporter
{
    public function __construct(
        private readonly MercadoPagoClient $client,
        private readonly CsvImporter $importer,
        private readonly CategoryMatcher $matcher,
        private readonly KnownAccountsDictionary $dictionary,
    ) {}

    public function import(int $days = 730): ImportBatch
    {
        $me = $this->client->me();
        $myId = (string) $me['id'];

        $account = Account::firstOrCreate(
            ['name' => config('finance.mercadopago.account_name')],
            ['type' => 'wallet', 'color' => '#00b1ea'],
        );

        // 1. Busca todos os pagamentos indexados na API (sem filtro restritivo de data para pegar o máximo possível)
        $paymentsById = [];
        foreach ([[], ['payer.id' => $myId]] as $filters) {
            foreach ($this->fetchAllPayments(null, null, $filters) as $payment) {
                $paymentsById[(string) $payment['id']] = $payment;
            }
        }

        // 2. Remove do banco quaisquer compras de Mercado Livre feitas no cartão de crédito que tenham sido importadas antes
        $creditCardExternalIds = [];
        foreach ($paymentsById as $pid => $payment) {
            if (! $this->isAccountBalancePayment($payment, $myId)) {
                $creditCardExternalIds[] = 'mp:'.$pid;
            }
        }
        if ($creditCardExternalIds !== []) {
            Transaction::withTrashed()
                ->where('account_id', $account->id)
                ->whereIn('external_id', $creditCardExternalIds)
                ->forceDelete();
        }

        // 3. Processa todos os relatórios de movimentações (/v1/account/release_report) disponíveis na conta
        $rowsByExternalId = [];
        $reports = $this->client->listReleaseReports();

        foreach ($reports as $rep) {
            $fileName = (string) ($rep['file_name'] ?? '');
            if ($fileName === '' || ($rep['status'] ?? '') !== 'enabled') {
                continue;
            }

            $csv = $this->client->downloadReleaseReport($fileName);
            $lines = preg_split('/\r\n|\r|\n/', trim($csv)) ?: [];
            array_shift($lines); // Remove cabeçalho

            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $cols = str_getcsv($line, ';');
                if (count($cols) < 6) {
                    continue;
                }

                $rawDate = trim((string) ($cols[0] ?? ''));
                $sourceId = trim((string) ($cols[1] ?? ''));
                $recordType = trim((string) ($cols[2] ?? ''));
                $credit = (float) ($cols[3] ?? 0);
                $debit = (float) ($cols[4] ?? 0);

                // Ignora linhas de saldo inicial/final ou bloqueios internos transitórios (reserve_for_*)
                if ($rawDate === '' || $recordType === '' || str_starts_with($recordType, 'reserve_for_')) {
                    continue;
                }

                $date = CarbonImmutable::parse($rawDate)->setTimezone(config('app.timezone'));

                if ($recordType === 'payment' && $sourceId !== '') {
                    $payment = $paymentsById[$sourceId] ?? null;
                    if (! $payment) {
                        $payment = $this->client->getPayment($sourceId);
                        if ($payment) {
                            $paymentsById[$sourceId] = $payment;
                        }
                    }

                    if ($payment) {
                        if (! $this->isAccountBalancePayment($payment, $myId)) {
                            continue;
                        }
                        $mapped = $this->mapPayment($payment, $myId);
                        $rowsByExternalId[$mapped['external_id']] = $mapped;
                    } else {
                        $netVal = $credit > 0 ? $credit : -$debit;
                        $cents = Money::parse(abs($netVal));
                        $extId = 'mp:'.$sourceId;
                        $desc = $netVal >= 0 ? "MP Pix Recebido (#{$sourceId})" : "MP Pagamento com Saldo (#{$sourceId})";
                        $rowsByExternalId[$extId] = [
                            'date' => $date->toDateString(),
                            'description' => $desc,
                            'amount_cents' => $netVal >= 0 ? abs($cents) : -abs($cents),
                            'installment' => null,
                            'installments' => null,
                            'external_id' => $extId,
                            'hash' => sha1('mercadopago|'.$extId),
                            'category_id' => $this->matcher->match($desc),
                        ];
                    }
                } elseif ($recordType === 'payout' && $sourceId !== '') {
                    $cents = Money::parse($debit > 0 ? $debit : abs($credit));
                    $extId = 'mp:payout:'.$sourceId;

                    $dictEntry = $this->dictionary->findByAccountId($sourceId);
                    $desc = $dictEntry
                        ? $this->dictionary->buildDescriptionFromEntry($dictEntry)
                        : "MP Transferência / Pix Enviado (#{$sourceId})";

                    $rowsByExternalId[$extId] = [
                        'date' => $date->toDateString(),
                        'description' => $desc,
                        'amount_cents' => -abs($cents),
                        'installment' => null,
                        'installments' => null,
                        'external_id' => $extId,
                        'hash' => sha1('mercadopago|'.$extId),
                        'category_id' => $this->matcher->match($desc),
                    ];
                } elseif ($recordType === 'payout_cancel' && $sourceId !== '') {
                    $cents = Money::parse($credit > 0 ? $credit : abs($debit));
                    $extId = 'mp:payout_cancel:'.$sourceId;

                    $dictEntry = $this->dictionary->findByAccountId($sourceId);
                    $desc = $dictEntry
                        ? 'MP Estorno: '.$this->dictionary->buildDescriptionFromEntry($dictEntry)
                        : "MP Estorno de Transferência (#{$sourceId})";

                    $rowsByExternalId[$extId] = [
                        'date' => $date->toDateString(),
                        'description' => $desc,
                        'amount_cents' => abs($cents),
                        'installment' => null,
                        'installments' => null,
                        'external_id' => $extId,
                        'hash' => sha1('mercadopago|'.$extId),
                        'category_id' => $this->matcher->match($desc),
                    ];
                } elseif (in_array($recordType, ['asset_management', 'asset_management_return'], true) && $sourceId !== '') {
                    $netVal = $credit - $debit;
                    if (abs($netVal) < 0.005) {
                        continue;
                    }
                    $cents = Money::parse(abs($netVal));
                    $extId = 'mp:yield:'.$sourceId;
                    $desc = 'MP Rendimento Conta (CDI)';
                    $rowsByExternalId[$extId] = [
                        'date' => $date->toDateString(),
                        'description' => $desc,
                        'amount_cents' => $netVal >= 0 ? abs($cents) : -abs($cents),
                        'installment' => null,
                        'installments' => null,
                        'external_id' => $extId,
                        'hash' => sha1('mercadopago|'.$extId),
                        'category_id' => $this->matcher->match('Rendimento CDI Investimentos'),
                    ];
                }
            }
        }

        // 4. Complementa com pagamentos recentes de /v1/payments/search (ex: Pix recebidos hoje após o último relatório)
        foreach ($paymentsById as $payment) {
            if (($payment['status'] ?? null) !== 'approved') {
                continue;
            }
            if (! $this->isAccountBalancePayment($payment, $myId)) {
                continue;
            }

            $mapped = $this->mapPayment($payment, $myId);
            if (! isset($rowsByExternalId[$mapped['external_id']])) {
                $rowsByExternalId[$mapped['external_id']] = $mapped;
            }
        }

        $rows = array_values($rowsByExternalId);

        // 5. Verifica transações já existentes e atualiza descrições se enriquecidas pelo Dicionário de Contas
        $existing = Transaction::withTrashed()
            ->where('account_id', $account->id)
            ->whereIn('external_id', array_column($rows, 'external_id'))
            ->get()
            ->keyBy('external_id');

        foreach ($rows as &$row) {
            /** @var Transaction|null $existingTx */
            $existingTx = $existing->get($row['external_id']);
            if ($existingTx) {
                $row['duplicate'] = true;
                if ($existingTx->description !== $row['description'] || $existingTx->amount_cents !== $row['amount_cents']) {
                    $existingTx->update([
                        'description' => $row['description'],
                        'amount_cents' => $row['amount_cents'],
                        'category_id' => $existingTx->category_id ?: $row['category_id'],
                    ]);
                }
            } else {
                $row['duplicate'] = false;
            }
        }
        unset($row);

        // Como temos 100% das movimentações desde a abertura da conta (saldo inicial 0,00 em mai/2025),
        // garante que initial_balance_cents seja 0 para o saldo bater no centavo com o Mercado Pago.
        if ($account->initial_balance_cents !== 0) {
            $account->update(['initial_balance_cents' => 0]);
        }

        return $this->importer->commit($rows, $account, null, 'API Mercado Pago (Histórico Completo)', 'mercadopago');
    }

    /**
     * Define se um pagamento movimentou de fato o saldo da conta Mercado Pago.
     * Desconsidera compras feitas no Mercado Livre com cartão de crédito/débito externo ou boleto.
     */
    public function isAccountBalancePayment(array $payment, string $myId): bool
    {
        $paymentType = (string) ($payment['payment_type_id'] ?? '');

        // Compras pagas com cartão de crédito (ex: cartão Nubank usado no Mercado Livre) nunca saem do saldo da conta MP
        if (in_array($paymentType, ['credit_card', 'debit_card', 'ticket', 'prepaid_card'], true)) {
            $isIncoming = (string) ($payment['collector_id'] ?? '') === $myId;
            if (! $isIncoming) {
                return false;
            }
        }

        // Também ignora pagamentos em que collector_id está vazio e payment_type_id é credit_card
        if ($paymentType === 'credit_card') {
            return false;
        }

        return true;
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

        $date = CarbonImmutable::parse($payment['date_approved'] ?? $payment['date_created'])
            ->setTimezone(config('app.timezone'));

        $description = $this->resolveDescription($payment, $value, $date);

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

    protected function resolveDescription(array $payment, float $value, CarbonImmutable $date): string
    {
        $bankInfo = $payment['point_of_interaction']['transaction_data']['bank_info'] ?? [];
        $payerBank = $bankInfo['payer'] ?? [];
        $longName = trim((string) ($payerBank['long_name'] ?? ''));
        $branch = trim((string) ($payerBank['branch'] ?? ''));
        $accountId = trim((string) ($payerBank['account_id'] ?? ''));
        $isSameOwner = (bool) ($bankInfo['is_same_bank_account_owner']
            ?? $payment['additional_info']['bank_info']['is_same_bank_account_owner']
            ?? false);

        if ($branch !== '' && $accountId !== '') {
            $sampleHint = 'R$ '.number_format($value, 2, ',', '.').' em '.$date->format('d/m/Y');

            return $this->dictionary->formatPixDescription(
                $longName ?: 'Pix',
                $branch,
                $accountId,
                $isSameOwner,
                $sampleHint
            );
        }

        if ($isSameOwner && $longName !== '') {
            $shortBank = KnownAccountsDictionary::shortenBankName($longName);

            return "MP Pix: Própria Titularidade ({$shortBank})";
        }

        $rawDesc = trim((string) ($payment['description'] ?? ''));
        if ($rawDesc === '' || $rawDesc === '-') {
            $rawDesc = (string) ($payment['payment_method_id'] ?? 'Mercado Pago');
        }

        return 'MP: '.$rawDesc;
    }

    /**
     * @return \Generator<int, array>
     */
    private function fetchAllPayments(?CarbonImmutable $begin, ?CarbonImmutable $end, array $filters): \Generator
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
