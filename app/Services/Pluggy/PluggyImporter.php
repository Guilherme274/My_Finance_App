<?php

namespace App\Services\Pluggy;

use App\Models\Account;
use App\Models\ImportBatch;
use App\Models\Investment;
use App\Models\Setting;
use App\Models\Transaction;
use App\Services\CategoryMatcher;
use App\Services\Import\CsvImporter;
use App\Support\Money;
use Carbon\CarbonImmutable;

/**
 * Sincroniza um Item conectado via Pluggy Open Finance (ex.: Nubank):
 * - Cria/atualiza as contas (Conta Corrente e Cartão de Crédito) e ajusta o saldo real;
 * - Importa todas as transações do período sem duplicar (external_id = pluggy:{id});
 * - Atualiza investimentos/Caixinhas se houver.
 */
class PluggyImporter
{
    public function __construct(
        private readonly PluggyClient $client,
        private readonly CsvImporter $importer,
        private readonly CategoryMatcher $matcher,
    ) {}

    /**
     * Retorna a lista de Items conectados salvos em Setting ('pluggy_items').
     *
     * @return array<string, array{id: string, connector_name: string, last_sync_at: ?string, status: ?string}>
     */
    public function connectedItems(): array
    {
        $raw = Setting::get('pluggy_items', '[]');
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function registerItem(string $itemId): array
    {
        $item = $this->client->getItem($itemId);
        $items = $this->connectedItems();

        $connectorName = $item['connector']['name'] ?? 'Nubank (Open Finance)';

        $items[$itemId] = [
            'id' => $itemId,
            'connector_name' => $connectorName,
            'last_sync_at' => now()->toDateTimeString(),
            'status' => $item['status'] ?? 'UPDATED',
        ];

        Setting::put('pluggy_items', json_encode($items, JSON_UNESCAPED_UNICODE));

        return $items[$itemId];
    }

    public function removeItem(string $itemId): void
    {
        $items = $this->connectedItems();
        unset($items[$itemId]);
        Setting::put('pluggy_items', json_encode($items, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Sincroniza contas, saldos, transações e investimentos de um Item da Pluggy.
     *
     * @return array{imported: int, skipped: int, accounts_synced: int, investments_synced: int}
     */
    public function syncItem(string $itemId, int $days = 90): array
    {
        $itemInfo = $this->registerItem($itemId);
        $connectorName = $itemInfo['connector_name'] ?? 'Nubank';

        $pluggyAccounts = $this->client->getAccounts($itemId);
        $end = CarbonImmutable::now();
        $begin = $end->subDays(max(1, min(365, $days)))->startOfDay();

        $totalImported = 0;
        $totalSkipped = 0;
        $accountsSynced = 0;

        foreach ($pluggyAccounts as $pAcc) {
            $isCredit = strtoupper((string) ($pAcc['type'] ?? '')) === 'CREDIT';
            $accType = $isCredit ? 'credit_card' : 'checking';
            $defaultName = $isCredit
                ? (str_contains(mb_strtolower($connectorName), 'nubank') ? 'Nubank - Cartão' : "{$connectorName} - Cartão")
                : (str_contains(mb_strtolower($connectorName), 'nubank') ? 'Nubank - Conta' : "{$connectorName} - Conta");

            $account = Account::firstOrCreate(
                ['name' => $defaultName],
                [
                    'type' => $accType,
                    'color' => str_contains(mb_strtolower($connectorName), 'nubank') ? '#8A05BE' : '#4edea3',
                ]
            );

            $txList = $this->client->getTransactions((string) $pAcc['id'], $begin, $end);
            $rows = [];

            foreach ($txList as $tx) {
                $status = strtoupper((string) ($tx['status'] ?? 'POSTED'));
                if ($status === 'PENDING') {
                    continue;
                }
                $rows[] = $this->mapTransaction($tx, $isCredit);
            }

            if ($rows) {
                $existing = Transaction::withTrashed()
                    ->where('account_id', $account->id)
                    ->whereIn('external_id', array_column($rows, 'external_id'))
                    ->pluck('external_id')
                    ->flip();

                foreach ($rows as &$row) {
                    $row['duplicate'] = $existing->has($row['external_id']);
                }
                unset($row);

                /** @var ImportBatch $batch */
                $batch = $this->importer->commit($rows, $account, null, "Open Finance: {$defaultName}", 'pluggy');
                $totalImported += (int) $batch->rows_imported;
                $totalSkipped += (int) $batch->rows_skipped;
            }

            // Ajusta o initial_balance_cents em contas correntes para que balanceCents() reflita o saldo real do Open Finance
            if (! $isCredit && isset($pAcc['balance']) && is_numeric($pAcc['balance'])) {
                $realBalanceCents = Money::parse((float) $pAcc['balance']);
                $txSumCents = (int) $account->transactions()->sum('amount_cents');
                $account->update([
                    'initial_balance_cents' => $realBalanceCents - $txSumCents,
                ]);
            }

            $accountsSynced++;
        }

        // Sincroniza Investimentos / Caixinhas do Item
        $investmentsSynced = 0;
        try {
            $pluggyInvestments = $this->client->getInvestments($itemId);
            foreach ($pluggyInvestments as $pInv) {
                $balanceCents = Money::parse((float) ($pInv['balance'] ?? $pInv['amount'] ?? 0));
                if ($balanceCents <= 0) {
                    continue;
                }

                $invName = trim((string) ($pInv['name'] ?? 'Caixinha / Investimento'));
                $invType = strtoupper((string) ($pInv['type'] ?? 'FIXED_INCOME'));
                $assetClass = match ($invType) {
                    'EQUITY', 'ETF', 'REAL_ESTATE_FUND' => 'stocks_fii',
                    default => str_contains(mb_strtolower($invName), 'reserva') || str_contains(mb_strtolower($invName), 'caixinha')
                        ? 'emergency'
                        : 'fixed_income',
                };

                Investment::updateOrCreate(
                    ['name' => $invName],
                    [
                        'ticker' => $pInv['code'] ?? strtoupper(substr($invName, 0, 6)),
                        'asset_class' => $assetClass,
                        'subtitle' => "{$connectorName} Open Finance",
                        'balance_cents' => $balanceCents,
                        'daily_change_pct' => (float) ($pInv['lastMonthRate'] ?? $pInv['annualRate'] ?? 0.95),
                        'icon' => $assetClass === 'emergency' ? 'shield_with_heart' : 'account_balance',
                    ]
                );
                $investmentsSynced++;
            }
        } catch (\Throwable) {
            // Alguns conectores não habilitam escopo de investimentos por padrão
        }

        return [
            'imported' => $totalImported,
            'skipped' => $totalSkipped,
            'accounts_synced' => $accountsSynced,
            'investments_synced' => $investmentsSynced,
        ];
    }

    /**
     * @param  array<string, mixed>  $tx
     * @return array<string, mixed>
     */
    public function mapTransaction(array $tx, bool $isCreditCard): array
    {
        $rawAmount = (float) ($tx['amount'] ?? 0);
        $txType = strtoupper((string) ($tx['type'] ?? ''));

        // Na Pluggy: DEBIT representa saída/compra, CREDIT representa entrada/pagamento de fatura
        if ($txType === 'DEBIT') {
            $signedAmount = -abs($rawAmount);
        } elseif ($txType === 'CREDIT') {
            $signedAmount = abs($rawAmount);
        } else {
            // Fallback por sinal
            $signedAmount = $isCreditCard ? -$rawAmount : $rawAmount;
        }

        $cents = Money::parse($signedAmount);
        $description = trim((string) ($tx['description'] ?? $tx['descriptionRaw'] ?? 'Lançamento Open Finance'));

        $installment = $tx['creditCardMetadata']['installmentNumber'] ?? null;
        $totalInstallments = $tx['creditCardMetadata']['totalInstallments'] ?? null;

        $date = CarbonImmutable::parse($tx['date'] ?? now())
            ->setTimezone(config('app.timezone'));

        return [
            'date' => $date->toDateString(),
            'description' => mb_substr($description, 0, 250),
            'amount_cents' => $cents,
            'installment' => $installment ? (int) $installment : null,
            'installments' => $totalInstallments ? (int) $totalInstallments : null,
            'external_id' => 'pluggy:'.$tx['id'],
            'hash' => sha1('pluggy|'.$tx['id']),
            'category_id' => $this->matcher->match($description),
        ];
    }
}
