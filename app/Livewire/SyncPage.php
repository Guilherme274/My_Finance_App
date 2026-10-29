<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Services\Sync\SyncClient;
use App\Services\Sync\SyncService;
use App\Services\Sync\SyncSettings;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

#[Layout('layouts.app')]
#[Title('Sincronizar')]
class SyncPage extends Component
{
    use WithFileUploads;

    public string $hubUrls = '';

    public string $token = '';

    public ?array $lastResult = null;

    public ?string $errorMessage = null;

    public $backupFile;

    public function mount(): void
    {
        $this->hubUrls = (string) Setting::get('sync_hub_urls', '');
        $this->token = (string) Setting::get('sync_token', '');
    }

    public function saveSettings(): void
    {
        Setting::put('sync_hub_urls', trim($this->hubUrls));
        Setting::put('sync_token', trim($this->token));
        session()->flash('status', 'Configurações de sincronização salvas.');
    }

    public function syncNow(SyncClient $client): void
    {
        $this->errorMessage = null;
        $this->lastResult = null;
        $this->saveSettings();

        try {
            $this->lastResult = $client->sync();
            session()->flash('status', 'Sincronizado com sucesso via '.$this->lastResult['url']);
        } catch (Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function resetSyncCursors(): void
    {
        Setting::put('sync_last_push_at', null);
        Setting::put('sync_last_pull_at', null);
        session()->flash('status', 'Cursores redefinidos. O próximo sync enviará/receberá todos os registros.');
    }

    public function regenerateHubToken(SyncSettings $settings): void
    {
        $settings->regenerateHubToken();
        session()->flash('status', 'Novo token gerado. Atualize-o no celular.');
    }

    public function exportBackup(SyncService $sync): StreamedResponse
    {
        $payload = [
            'app' => 'gestao-financeira',
            'exported_at' => $sync->now(),
            'changes' => $sync->changesSince(null),
        ];

        $filename = 'backup-financas-'.now()->format('Y-m-d_His').'.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function importBackup(SyncService $sync): void
    {
        $this->validate(['backupFile' => ['required', 'file', 'max:20480']]);

        $data = json_decode(file_get_contents($this->backupFile->getRealPath()), true);
        if (($data['app'] ?? null) !== 'gestao-financeira' || ! is_array($data['changes'] ?? null)) {
            $this->addError('backupFile', 'Arquivo de backup inválido.');

            return;
        }

        $stats = $sync->apply($data['changes']);
        $this->reset('backupFile');
        session()->flash('status', "Backup aplicado: {$stats['inserted']} inserido(s), {$stats['updated']} atualizado(s).");
    }

    public function render(SyncSettings $settings)
    {
        $isHub = config('finance.role') === 'hub';

        return view('livewire.sync-page', [
            'isHub' => $isHub,
            'hubToken' => $isHub ? $settings->hubToken() : null,
            'addresses' => $isHub ? $settings->localAddresses() : [],
            'port' => config('finance.sync.port'),
            'lastSuccess' => Setting::get('sync_last_success_at'),
            'lastUrl' => Setting::get('sync_last_url'),
        ]);
    }
}
