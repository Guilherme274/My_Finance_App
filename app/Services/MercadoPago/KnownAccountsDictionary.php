<?php

namespace App\Services\MercadoPago;

use App\Models\Setting;
use App\Models\Transaction;

/**
 * Gerencia o Dicionário de Contas Conhecidas (Agência + Conta -> Nome do Titular).
 *
 * Permite identificar remetentes recorrentes de Pix no Mercado Pago mesmo quando
 * a API mascara o first_name/CPF por conta da LGPD.
 */
class KnownAccountsDictionary
{
    public const SETTING_KEY = 'mp_known_bank_accounts';

    /**
     * Retorna todas as contas conhecidas/detectadas indexadas por "acc_{branch}_{account_id}".
     *
     * @return array<string, array{
     *     key: string,
     *     bank_name: string,
     *     branch: string,
     *     account_id: string,
     *     label: string,
     *     is_same_owner: bool,
     *     sample_hint: string
     * }>
     */
    public function all(): array
    {
        $raw = Setting::get(self::SETTING_KEY);
        $decoded = $raw ? json_decode((string) $raw, true) : [];
        if (! is_array($decoded)) {
            $decoded = [];
        }

        // Normaliza chaves antigas ("branch:account") para chaves seguras ("acc_branch_account")
        $stored = [];
        $needsMigrationSave = false;
        foreach ($decoded as $k => $item) {
            if (! is_array($item)) {
                continue;
            }
            $branch = (string) ($item['branch'] ?? '');
            $accountId = (string) ($item['account_id'] ?? '');
            $normKey = ($branch !== '' && $accountId !== '') ? self::makeKey($branch, $accountId) : (string) $k;
            if ((string) $k !== $normKey) {
                $needsMigrationSave = true;
            }
            $item['key'] = $normKey;
            $stored[$normKey] = $item;
        }

        $defaults = $this->defaultAccounts();

        foreach ($defaults as $key => $defaultItem) {
            if (! isset($stored[$key])) {
                $stored[$key] = $defaultItem;
                $needsMigrationSave = true;
            } else {
                // Preserva o label salvo pelo usuário mesmo se for string vazia ou preenchida
                $savedLabel = $stored[$key]['label'] ?? null;
                $stored[$key] = array_merge($defaultItem, $stored[$key]);
                if ($savedLabel !== null) {
                    $stored[$key]['label'] = (string) $savedLabel;
                }
            }
        }

        if ($needsMigrationSave) {
            $this->saveAll($stored);
        }

        return $stored;
    }

    public function saveAll(array $accounts): void
    {
        Setting::put(self::SETTING_KEY, json_encode($accounts, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Registra ou atualiza os metadados de uma conta detectada na API do Mercado Pago.
     */
    public function recordDetectedAccount(
        string $bankName,
        string $branch,
        string $accountId,
        bool $isSameOwner,
        ?string $sampleHint = null
    ): array {
        $key = self::makeKey($branch, $accountId);
        $all = $this->all();

        $shortBank = self::shortenBankName($bankName);

        if (! isset($all[$key])) {
            $all[$key] = [
                'key' => $key,
                'bank_name' => $shortBank,
                'branch' => $branch,
                'account_id' => $accountId,
                'label' => $isSameOwner ? 'Própria Titularidade (Guilherme)' : '',
                'is_same_owner' => $isSameOwner,
                'sample_hint' => $sampleHint ?? '',
            ];
            $this->saveAll($all);
        } elseif ($sampleHint && empty($all[$key]['sample_hint'])) {
            $all[$key]['sample_hint'] = $sampleHint;
            $this->saveAll($all);
        }

        return $all[$key];
    }

    /**
     * Atualiza imediatamente o nome/apelido de uma única conta no dicionário e já reflete nos lançamentos.
     */
    public function updateLabel(string $key, string $label): int
    {
        $all = $this->all();
        if (! isset($all[$key])) {
            return 0;
        }

        $all[$key]['label'] = trim($label);
        $this->saveAll($all);

        return $this->applySingleToTransactions($all[$key]);
    }

    public function findByAccountId(string $accountId): ?array
    {
        $cleanAcc = preg_replace('/[^a-zA-Z0-9]/', '', trim($accountId));
        if ($cleanAcc === '') {
            return null;
        }

        foreach ($this->all() as $item) {
            $itemAcc = preg_replace('/[^a-zA-Z0-9]/', '', (string) ($item['account_id'] ?? ''));
            if ($itemAcc === $cleanAcc) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Adiciona manualmente uma nova conta ao dicionário.
     */
    public function addCustom(string $bankName, string $branch, string $accountId, string $label, bool $isSameOwner = false): void
    {
        $branch = trim($branch);
        $accountId = trim($accountId);
        if ($accountId === '') {
            return;
        }
        if ($branch === '') {
            $branch = '1';
        }

        $key = self::makeKey($branch, $accountId);
        $all = $this->all();
        $all[$key] = [
            'key' => $key,
            'bank_name' => self::shortenBankName($bankName ?: 'Banco'),
            'branch' => $branch,
            'account_id' => $accountId,
            'label' => trim($label),
            'is_same_owner' => $isSameOwner,
            'sample_hint' => $all[$key]['sample_hint'] ?? 'Adicionada manualmente',
        ];

        $this->saveAll($all);
    }

    public function remove(string $key): void
    {
        $all = $this->all();
        unset($all[$key]);
        $this->saveAll($all);
    }

    /**
     * Monta a descrição amigável para um pagamento Pix do Mercado Pago.
     */
    public function formatPixDescription(
        string $bankName,
        string $branch,
        string $accountId,
        bool $isSameOwner,
        ?string $sampleHint = null
    ): string {
        $entry = $this->recordDetectedAccount($bankName, $branch, $accountId, $isSameOwner, $sampleHint);

        return $this->buildDescriptionFromEntry($entry);
    }

    public function buildDescriptionFromEntry(array $entry): string
    {
        $shortBank = $entry['bank_name'] ?: 'Pix';
        $branch = trim((string) ($entry['branch'] ?? ''));
        $accountId = trim((string) ($entry['account_id'] ?? ''));
        $isSameOwner = (bool) ($entry['is_same_owner'] ?? false);
        $label = trim((string) ($entry['label'] ?? ''));

        $accParts = [];
        if ($branch !== '' && $branch !== '-') {
            $accParts[] = "Ag. {$branch}";
        }
        if ($accountId !== '') {
            $accParts[] = "C. {$accountId}";
        }
        $accInfo = implode(' ', $accParts);
        $bankDetails = trim("{$shortBank} {$accInfo}");

        if ($isSameOwner) {
            $ownerText = $label !== '' ? $label : 'Própria Titularidade';

            return $bankDetails !== '' ? "MP Pix: {$ownerText} ({$bankDetails})" : "MP Pix: {$ownerText}";
        }

        if ($label !== '') {
            return $bankDetails !== '' ? "MP Pix: {$label} ({$bankDetails})" : "MP Pix: {$label}";
        }

        return $bankDetails !== '' ? "MP Pix: {$bankDetails}" : 'MP Pix';
    }

    /**
     * Atualiza as descrições de transações de uma única conta do dicionário.
     */
    public function applySingleToTransactions(array $entry): int
    {
        $branch = trim((string) ($entry['branch'] ?? ''));
        $accountId = trim((string) ($entry['account_id'] ?? ''));
        $targetDesc = $this->buildDescriptionFromEntry($entry);

        $cleanAcc = preg_replace('/[^a-zA-Z0-9]/', '', $accountId);
        $cleanBranch = preg_replace('/[^a-zA-Z0-9]/', '', $branch);

        if ($cleanAcc === '') {
            return 0;
        }

        $txs = Transaction::where('source', 'mercadopago')
            ->where(function ($q) use ($branch, $accountId, $cleanAcc, $cleanBranch) {
                // Correspondência padrão com agência e conta
                if ($branch !== '' && $accountId !== '') {
                    $q->where('description', 'like', "%Ag. {$branch} C. {$accountId}%")
                        ->orWhere('description', 'like', "%Ag. {$cleanBranch} C. {$cleanAcc}%");
                }
                // Correspondência por número de conta isolado
                $q->orWhere('description', 'like', "%C. {$accountId}%")
                    ->orWhere('description', 'like', "%C. {$cleanAcc}%");

                // Correspondência por ID de operação / payout (#182429240030)
                $q->orWhere('description', 'like', "%(#{$accountId})%")
                    ->orWhere('description', 'like', "%(#{$cleanAcc})%")
                    ->orWhere('description', 'like', "%#{$accountId}%")
                    ->orWhere('description', 'like', "%#{$cleanAcc}%");

                // Correspondência por external_id
                $q->orWhere('external_id', 'mp:payout:'.$accountId)
                    ->orWhere('external_id', 'mp:payout:'.$cleanAcc)
                    ->orWhere('external_id', 'mp:'.$accountId)
                    ->orWhere('external_id', 'mp:'.$cleanAcc);
            })
            ->get();

        $updatedCount = 0;
        foreach ($txs as $tx) {
            if ($tx->description !== $targetDesc) {
                $tx->update(['description' => $targetDesc]);
                $updatedCount++;
            }
        }

        return $updatedCount;
    }

    /**
     * Atualiza as descrições de transações já existentes no banco de dados para todas as contas.
     */
    public function applyToExistingTransactions(): int
    {
        $all = $this->all();
        $updatedCount = 0;

        foreach ($all as $entry) {
            $updatedCount += $this->applySingleToTransactions($entry);
        }

        return $updatedCount;
    }

    public static function makeKey(string $branch, string $accountId): string
    {
        $cleanBranch = preg_replace('/[^a-zA-Z0-9]/', '', trim($branch)) ?: '0';
        $cleanAcc = preg_replace('/[^a-zA-Z0-9]/', '', trim($accountId));

        return "acc_{$cleanBranch}_{$cleanAcc}";
    }

    public static function shortenBankName(string $longName): string
    {
        $upper = mb_strtoupper(trim($longName));

        if (str_contains($upper, 'ITAÚ') || str_contains($upper, 'ITAU')) {
            return 'Itaú Unibanco';
        }
        if (str_contains($upper, 'BRADESCO')) {
            return 'Bradesco';
        }
        if (str_contains($upper, 'NU PAGAMENTOS') || str_contains($upper, 'NUBANK')) {
            return 'Nubank';
        }
        if (str_contains($upper, 'BANCO DO BRASIL')) {
            return 'Banco do Brasil';
        }
        if (str_contains($upper, 'C6')) {
            return 'C6 Bank';
        }
        if (str_contains($upper, 'INTER')) {
            return 'Banco Inter';
        }
        if (str_contains($upper, 'SANTANDER')) {
            return 'Santander';
        }
        if (str_contains($upper, 'CAIXA')) {
            return 'Caixa';
        }
        if (str_contains($upper, '99PAY')) {
            return '99Pay';
        }

        return $longName !== '' ? mb_substr($longName, 0, 28) : 'Pix';
    }

    protected function defaultAccounts(): array
    {
        $items = [
            [
                'bank_name' => 'Itaú Unibanco',
                'branch' => '4830',
                'account_id' => '543015',
                'label' => 'Própria Titularidade (Guilherme)',
                'is_same_owner' => true,
                'sample_hint' => 'R$ 1.531,00 (06/10), R$ 220,50 (01/10), R$ 1.411,00 (04/09)',
            ],
            [
                'bank_name' => 'Bradesco',
                'branch' => '2965',
                'account_id' => '10071968',
                'label' => '',
                'is_same_owner' => false,
                'sample_hint' => 'R$ 1.050,00 mensais (05/10, 04/09, 03/08, 06/07)',
            ],
            [
                'bank_name' => 'Nubank',
                'branch' => '1',
                'account_id' => '3410645425',
                'label' => '',
                'is_same_owner' => false,
                'sample_hint' => 'R$ 103,44 em 06/10',
            ],
            [
                'bank_name' => 'Banco do Brasil',
                'branch' => '5773',
                'account_id' => '7838',
                'label' => '',
                'is_same_owner' => false,
                'sample_hint' => 'R$ 130,00 (19/09) e R$ 220,00 (12/09)',
            ],
            [
                'bank_name' => 'Banco do Brasil',
                'branch' => '4',
                'account_id' => '879436',
                'label' => '',
                'is_same_owner' => false,
                'sample_hint' => 'R$ 103,44 (15/09) e R$ 8,00 (17/08, 12/07)',
            ],
            [
                'bank_name' => 'Nubank',
                'branch' => '1',
                'account_id' => '702248861',
                'label' => '',
                'is_same_owner' => false,
                'sample_hint' => 'R$ 100,00 em 24/08',
            ],
            [
                'bank_name' => 'C6 Bank',
                'branch' => '1',
                'account_id' => '370497481',
                'label' => '',
                'is_same_owner' => false,
                'sample_hint' => 'R$ 103,44 em 07/08',
            ],
            [
                'bank_name' => 'Itaú Unibanco',
                'branch' => '9186',
                'account_id' => '620051',
                'label' => '',
                'is_same_owner' => false,
                'sample_hint' => 'R$ 69,00 em 28/07',
            ],
        ];

        $out = [];
        foreach ($items as $item) {
            $key = self::makeKey($item['branch'], $item['account_id']);
            $item['key'] = $key;
            $out[$key] = $item;
        }

        return $out;
    }
}
