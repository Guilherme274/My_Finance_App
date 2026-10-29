<?php

namespace App\Livewire;

use App\Models\ImportBatch;
use App\Services\MercadoPago\KnownAccountsDictionary;
use App\Services\MercadoPago\MercadoPagoClient;
use App\Services\MercadoPago\MercadoPagoImporter;
use App\Services\Pluggy\PluggyClient;
use App\Services\Pluggy\PluggyImporter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Integrações & Open Finance')]
class Integrations extends Component
{
    public int $days = 730;

    public ?array $accountInfo = null;

    public ?string $errorMessage = null;

    public ?string $successMessage = null;

    public ?string $lastSavedKey = null;

    public string $manualItemId = '';

    /** @var array<string, string> */
    public array $dictionaryLabels = [];

    // Form para adicionar nova conta conhecida manualmente
    public string $newBankName = '';

    public string $newBranch = '';

    public string $newAccountId = '';

    public string $newLabel = '';

    public bool $newIsSameOwner = false;

    public function mount(KnownAccountsDictionary $dictionary): void
    {
        $this->loadDictionary($dictionary);
    }

    protected function loadDictionary(KnownAccountsDictionary $dictionary): void
    {
        $this->dictionaryLabels = [];
        foreach ($dictionary->all() as $key => $item) {
            $this->dictionaryLabels[$key] = (string) ($item['label'] ?? '');
        }
    }

    /**
     * Hook automático do Livewire: dispara assim que o usuário digita/altera qualquer campo em dictionaryLabels.{key}.
     */
    public function updatedDictionaryLabels(mixed $value, ?string $key = null): void
    {
        if (! $key) {
            return;
        }

        $dictionary = app(KnownAccountsDictionary::class);
        $dictionary->updateLabel($key, (string) $value);
        $this->lastSavedKey = $key;
    }

    /**
     * Chamado também no blur/change do input para garantir salvamento instantâneo.
     */
    public function updateAccountLabel(string $key, string $value, KnownAccountsDictionary $dictionary): void
    {
        $this->dictionaryLabels[$key] = $value;
        $dictionary->updateLabel($key, $value);
        $this->lastSavedKey = $key;
    }

    public function saveDictionary(KnownAccountsDictionary $dictionary): void
    {
        $all = $dictionary->all();
        foreach ($this->dictionaryLabels as $key => $label) {
            if (isset($all[$key])) {
                $all[$key]['label'] = trim((string) $label);
            }
        }

        $dictionary->saveAll($all);
        $updated = $dictionary->applyToExistingTransactions();
        $this->loadDictionary($dictionary);

        $this->successMessage = "Dicionário de Contas salvo! {$updated} lançamento(s) existente(s) atualizado(s) com os novos nomes.";
    }

    public function addDictionaryAccount(KnownAccountsDictionary $dictionary): void
    {
        if (trim($this->newAccountId) === '') {
            $this->errorMessage = 'Preencha pelo menos o Número da Conta ou ID da Operação.';

            return;
        }

        $branch = trim($this->newBranch);
        if ($branch === '') {
            $branch = '1';
        }

        $dictionary->addCustom(
            $this->newBankName ?: 'Banco',
            $branch,
            $this->newAccountId,
            $this->newLabel,
            $this->newIsSameOwner
        );

        $updated = $dictionary->applyToExistingTransactions();

        $this->newBankName = '';
        $this->newBranch = '';
        $this->newAccountId = '';
        $this->newLabel = '';
        $this->newIsSameOwner = false;

        $this->loadDictionary($dictionary);
        $this->successMessage = "Conta adicionada ao dicionário! ({$updated} lançamento(s) atualizado(s)).";
    }

    public function removeDictionaryAccount(string $key, KnownAccountsDictionary $dictionary): void
    {
        $dictionary->remove($key);
        $this->loadDictionary($dictionary);
        $this->successMessage = 'Conta removida do dicionário.';
    }

    // --- Pluggy Open Finance (Nubank & Bancos) ---

    public function startPluggyConnect(PluggyClient $pluggy): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $connectToken = $pluggy->createConnectToken(null, 'gestao-financeira');
            $this->dispatch('open-pluggy-connect', token: $connectToken);
        } catch (Throwable $e) {
            $this->errorMessage = 'Erro ao gerar Connect Token da Pluggy: '.$e->getMessage();
        }
    }

    public function onPluggyItemConnected(string $itemId, PluggyImporter $importer): void
    {
        $this->errorMessage = null;

        try {
            $stats = $importer->syncItem($itemId, $this->days);
            $this->successMessage = "Nubank / Open Finance conectado! {$stats['accounts_synced']} conta(s), {$stats['imported']} transação(ões) nova(s) e {$stats['investments_synced']} investimento(s) sincronizados.";
        } catch (Throwable $e) {
            $this->errorMessage = 'Conta conectada, mas houve erro ao importar dados: '.$e->getMessage();
        }
    }

    public function syncPluggyItem(string $itemId, PluggyImporter $importer): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $stats = $importer->syncItem($itemId, $this->days);
            $this->successMessage = "Sincronização Open Finance concluída! {$stats['imported']} novo(s), {$stats['skipped']} já existente(s), {$stats['accounts_synced']} conta(s) atualizada(s).";
        } catch (Throwable $e) {
            $this->errorMessage = 'Erro ao sincronizar Open Finance: '.$e->getMessage();
        }
    }

    public function addManualPluggyItem(PluggyImporter $importer): void
    {
        $itemId = trim($this->manualItemId);
        if ($itemId === '') {
            return;
        }

        $this->manualItemId = '';
        $this->syncPluggyItem($itemId, $importer);
    }

    public function removePluggyItem(string $itemId, PluggyImporter $importer): void
    {
        $importer->removeItem($itemId);
        $this->successMessage = 'Conexão Open Finance removida.';
    }

    // --- Mercado Pago ---

    public function test(MercadoPagoClient $client): void
    {
        $this->errorMessage = null;
        try {
            $this->accountInfo = $client->me();
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function import(MercadoPagoImporter $importer, KnownAccountsDictionary $dictionary): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;

        try {
            $batch = $importer->import(max(1, min(1825, $this->days)));
            $this->loadDictionary($dictionary);
            $this->successMessage = "Mercado Pago sincronizado (Histórico Completo): {$batch->rows_imported} novo(s), {$batch->rows_skipped} atualizado(s)/existente(s).";
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(
        MercadoPagoClient $mpClient,
        PluggyClient $pluggyClient,
        PluggyImporter $pluggyImporter,
        KnownAccountsDictionary $dictionary
    ) {
        return view('livewire.integrations', [
            'configured' => $mpClient->isConfigured(),
            'accountName' => config('finance.mercadopago.account_name'),
            'pluggyConfigured' => $pluggyClient->isConfigured(),
            'pluggyItems' => $pluggyImporter->connectedItems(),
            'knownAccounts' => $dictionary->all(),
            'batches' => ImportBatch::whereIn('source', ['mercadopago', 'pluggy'])->latest()->limit(8)->get(),
        ]);
    }
}
