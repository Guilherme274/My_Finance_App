<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\BankProfile;
use App\Models\Category;
use App\Models\ImportBatch;
use App\Services\Import\CsvImporter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

#[Layout('layouts.app')]
#[Title('Importar CSV')]
class ImportCsv extends Component
{
    use WithFileUploads;

    public $file;

    public string $bank_profile_id = '';

    public string $account_id = '';

    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public ?string $filename = null;

    public function mount(): void
    {
        $nubank = BankProfile::where('slug', 'nubank-cartao')->first();
        $this->bank_profile_id = (string) ($nubank?->id ?? BankProfile::value('id'));
        $this->account_id = (string) (Account::where('name', 'Nubank - Cartão')->value('id') ?? Account::value('id'));
    }

    public function preview(CsvImporter $importer): void
    {
        $this->validate([
            'file' => ['required', 'file', 'max:5120'],
            'bank_profile_id' => ['required', Rule::exists('bank_profiles', 'id')],
            'account_id' => ['required', Rule::exists('accounts', 'id')],
        ]);

        try {
            $profile = BankProfile::findOrFail($this->bank_profile_id);
            $account = Account::findOrFail($this->account_id);
            $parsed = $importer->parse(file_get_contents($this->file->getRealPath()), $profile);
            $this->rows = array_map(fn ($r) => $r + ['skip' => false], $importer->preview($parsed, $account));
            $this->filename = $this->file->getClientOriginalName();
        } catch (Throwable $e) {
            $this->rows = [];
            $this->addError('file', $e->getMessage());
        }
    }

    public function confirm(CsvImporter $importer): void
    {
        if (! $this->rows) {
            return;
        }

        $batch = $importer->commit(
            $this->rows,
            Account::findOrFail($this->account_id),
            BankProfile::find($this->bank_profile_id),
            $this->filename,
        );

        $this->reset(['rows', 'file', 'filename']);
        session()->flash('status', "Importação concluída: {$batch->rows_imported} lançamento(s) novo(s), {$batch->rows_skipped} ignorado(s).");
    }

    public function cancel(): void
    {
        $this->reset(['rows', 'file', 'filename']);
    }

    public function undo(string $batchId, CsvImporter $importer): void
    {
        $importer->undo(ImportBatch::findOrFail($batchId));
        session()->flash('status', 'Importação desfeita.');
    }

    public function render()
    {
        $new = collect($this->rows)->where('duplicate', false)->where('skip', false);

        return view('livewire.import-csv', [
            'profiles' => BankProfile::orderBy('name')->get(),
            'accounts' => Account::where('archived', false)->orderBy('name')->get(),
            'categories' => Category::orderBy('kind')->orderBy('name')->get(),
            'newCount' => $new->count(),
            'newTotal' => (int) $new->sum('amount_cents'),
            'batches' => ImportBatch::with('account')->latest()->limit(10)->get(),
        ]);
    }
}
