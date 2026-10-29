<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Nova Transação')]
class NewTransaction extends Component
{
    use WithFileUploads;

    public ?string $editingId = null;

    public string $type = 'expense'; // 'expense' | 'income' | 'transfer'

    public int $amountCents = 0;

    public string $amountInput = '0,00';

    public string $description = '';

    public ?string $categoryId = null;

    public ?string $accountId = null;

    public ?string $destinationAccountId = null;

    public string $occurredOn = '';

    public string $datePreset = 'today'; // 'today' | 'yesterday' | 'custom'

    public int $installments = 1;

    public bool $showKeypad = false;

    public $receiptFile = null;

    public ?string $existingReceiptPath = null;

    public ?string $toastMessage = null;

    public bool $justSaved = false;

    public function mount(): void
    {
        $this->occurredOn = request()->query('data', now()->toDateString());
        if ($this->occurredOn === now()->toDateString()) {
            $this->datePreset = 'today';
        } elseif ($this->occurredOn === now()->subDay()->toDateString()) {
            $this->datePreset = 'yesterday';
        } else {
            $this->datePreset = 'custom';
        }

        $requestedType = request()->query('tipo');
        if (in_array($requestedType, ['expense', 'income', 'transfer'], true)) {
            $this->type = $requestedType;
        }

        $firstAccount = Account::orderBy('name')->first();
        $this->accountId = $firstAccount?->id;
        $secondAccount = Account::where('id', '!=', $this->accountId)->orderBy('name')->first();
        $this->destinationAccountId = $secondAccount?->id;

        $this->selectDefaultCategory();

        $editId = request()->query('editar');
        if ($editId) {
            $tx = Transaction::find($editId);
            if ($tx) {
                $this->editingId = $tx->id;
                $this->type = $tx->amount_cents < 0 ? 'expense' : 'income';
                $this->amountCents = abs((int) $tx->amount_cents);
                $this->syncInputFromCents();
                $this->description = $tx->description;
                $this->categoryId = $tx->category_id;
                $this->accountId = $tx->account_id;
                $this->occurredOn = $tx->date->toDateString();
                $this->datePreset = $this->occurredOn === now()->toDateString()
                    ? 'today'
                    : ($this->occurredOn === now()->subDay()->toDateString() ? 'yesterday' : 'custom');
                $this->existingReceiptPath = $tx->receipt_path;
            }
        }
    }

    public function setType(string $type): void
    {
        $this->type = $type;
        $this->selectDefaultCategory();
    }

    protected function selectDefaultCategory(): void
    {
        if ($this->categoryId && Category::whereKey($this->categoryId)->exists()) {
            return;
        }

        $kind = $this->type === 'income' ? 'income' : ($this->type === 'transfer' ? 'transfer' : 'expense');
        $cat = Category::where('kind', $kind)->orderBy('name')->first()
            ?? Category::orderBy('name')->first();
        $this->categoryId = $cat?->id;
    }

    public function updatedAmountInput(string $value): void
    {
        $digits = preg_replace('/\D/', '', $value);
        $this->amountCents = $digits !== '' ? (int) $digits : 0;
        $this->syncInputFromCents();
    }

    public function stepAmount(int $deltaReais): void
    {
        $this->amountCents = max(0, $this->amountCents + ($deltaReais * 100));
        $this->syncInputFromCents();
    }

    public function pressDigit(string $digit): void
    {
        if ($digit === 'C') {
            $this->amountCents = 0;
        } elseif ($digit === 'BACK') {
            $this->amountCents = intdiv($this->amountCents, 10);
        } elseif (ctype_digit($digit)) {
            if ($this->amountCents < 99999999) {
                $this->amountCents = ($this->amountCents * 10) + (int) $digit;
            }
        }
        $this->syncInputFromCents();
    }

    public function toggleKeypad(): void
    {
        $this->showKeypad = ! $this->showKeypad;
    }

    protected function syncInputFromCents(): void
    {
        $this->amountInput = number_format($this->amountCents / 100, 2, ',', '.');
    }

    public function selectDatePreset(string $preset): void
    {
        $this->datePreset = $preset;
        if ($preset === 'today') {
            $this->occurredOn = now()->toDateString();
        } elseif ($preset === 'yesterday') {
            $this->occurredOn = now()->subDay()->toDateString();
        }
    }

    public function updatedOccurredOn(): void
    {
        if ($this->occurredOn === now()->toDateString()) {
            $this->datePreset = 'today';
        } elseif ($this->occurredOn === now()->subDay()->toDateString()) {
            $this->datePreset = 'yesterday';
        } else {
            $this->datePreset = 'custom';
        }
    }

    public function selectCategory(string $id): void
    {
        $this->categoryId = $id;
    }

    public function selectAccount(string $id): void
    {
        $this->accountId = $id;
    }

    public function save(): void
    {
        if ($this->amountCents <= 0) {
            $this->addError('amountCents', 'Informe um valor maior que R$ 0,00.');
            $this->dispatch('emerald-feedback', message: 'Informe um valor maior que R$ 0,00.', sound: 'error');

            return;
        }

        if (trim($this->description) === '') {
            $catName = Category::find($this->categoryId)?->name;
            $this->description = $this->type === 'transfer'
                ? 'Transferência entre contas'
                : ($catName ?: 'Lançamento');
        }

        $receiptPath = $this->existingReceiptPath;
        if ($this->receiptFile) {
            $receiptPath = $this->receiptFile->store('receipts', 'public');
        }

        $signedCents = $this->type === 'income' ? abs($this->amountCents) : -abs($this->amountCents);

        if ($this->editingId) {
            $tx = Transaction::findOrFail($this->editingId);
            $tx->update([
                'account_id' => $this->accountId,
                'category_id' => $this->categoryId,
                'description' => $this->description,
                'amount_cents' => $signedCents,
                'date' => $this->occurredOn,
                'receipt_path' => $receiptPath,
            ]);
            $this->toastMessage = 'Alterações salvas com sucesso!';
            $this->justSaved = true;
            $this->dispatch('emerald-feedback', message: $this->toastMessage, sound: 'save');

            return;
        }

        if ($this->type === 'transfer') {
            $transferCatId = Category::where('kind', 'transfer')->value('id') ?? $this->categoryId;

            Transaction::create([
                'account_id' => $this->accountId,
                'category_id' => $transferCatId,
                'description' => $this->description.' (Saída)',
                'amount_cents' => -abs($this->amountCents),
                'date' => $this->occurredOn,
                'source' => 'manual',
                'receipt_path' => $receiptPath,
            ]);

            if ($this->destinationAccountId) {
                Transaction::create([
                    'account_id' => $this->destinationAccountId,
                    'category_id' => $transferCatId,
                    'description' => $this->description.' (Entrada)',
                    'amount_cents' => abs($this->amountCents),
                    'date' => $this->occurredOn,
                    'source' => 'manual',
                    'receipt_path' => $receiptPath,
                ]);
            }

            $this->resetAfterSave('Transferência registrada com sucesso!');

            return;
        }

        $totalInstallments = $this->type === 'expense' ? max(1, min(12, $this->installments)) : 1;
        $baseInstallmentCents = intdiv($this->amountCents, $totalInstallments);
        $remainderCents = $this->amountCents - ($baseInstallmentCents * $totalInstallments);
        $baseDate = Carbon::parse($this->occurredOn);

        for ($i = 1; $i <= $totalInstallments; $i++) {
            $cents = $baseInstallmentCents + ($i === 1 ? $remainderCents : 0);
            $signedItemCents = $this->type === 'income' ? $cents : -$cents;
            $desc = $totalInstallments > 1
                ? "{$this->description} ({$i}/{$totalInstallments})"
                : $this->description;

            Transaction::create([
                'account_id' => $this->accountId,
                'category_id' => $this->categoryId,
                'description' => $desc,
                'amount_cents' => $signedItemCents,
                'installment' => $totalInstallments > 1 ? $i : null,
                'installments' => $totalInstallments > 1 ? $totalInstallments : null,
                'date' => $baseDate->copy()->addMonths($i - 1)->toDateString(),
                'source' => 'manual',
                'receipt_path' => $receiptPath,
            ]);
        }

        $msg = $totalInstallments > 1
            ? "Transação parcelada em {$totalInstallments}x registrada com sucesso!"
            : 'Transação registrada com sucesso!';

        $this->resetAfterSave($msg);
    }

    protected function resetAfterSave(string $message): void
    {
        $this->amountCents = 0;
        $this->syncInputFromCents();
        $this->description = '';
        $this->installments = 1;
        $this->receiptFile = null;
        $this->toastMessage = $message;
        $this->justSaved = true;
        $this->dispatch('emerald-feedback', message: $message, sound: 'save');
    }

    public function render()
    {
        $categories = Category::orderBy('name')->get();
        $accounts = Account::where('archived', false)->orderBy('name')->get();

        $installmentCents = $this->installments > 0
            ? (int) round($this->amountCents / $this->installments)
            : $this->amountCents;

        return view('livewire.new-transaction', [
            'categories' => $categories,
            'accounts' => $accounts,
            'installmentCents' => $installmentCents,
        ]);
    }
}
