<?php

namespace App\Livewire;

use App\Models\Account;
use App\Support\Money;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Contas')]
class Accounts extends Component
{
    public bool $showForm = false;

    public ?string $editingId = null;

    public string $name = '';

    public string $type = 'checking';

    public string $initial_balance = '0,00';

    public string $color = '#0ea5e9';

    public ?int $closing_day = null;

    public ?int $due_day = null;

    public bool $archived = false;

    public function create(): void
    {
        $this->resetValidation();
        $this->reset(['editingId', 'name', 'type', 'initial_balance', 'color', 'closing_day', 'due_day', 'archived']);
        $this->showForm = true;
    }

    public function edit(string $id): void
    {
        $a = Account::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $a->id;
        $this->name = $a->name;
        $this->type = $a->type;
        $this->initial_balance = Money::format($a->initial_balance_cents, false);
        $this->color = $a->color;
        $this->closing_day = $a->closing_day;
        $this->due_day = $a->due_day;
        $this->archived = $a->archived;
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(Account::TYPES))],
            'color' => ['required', 'string', 'max:9'],
            'closing_day' => ['nullable', 'integer', 'between:1,31'],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
        ]);

        try {
            $initial = Money::parse($this->initial_balance ?: '0');
        } catch (InvalidArgumentException) {
            $this->addError('initial_balance', 'Valor inválido.');

            return;
        }

        Account::updateOrCreate(['id' => $this->editingId], [
            'name' => $this->name,
            'type' => $this->type,
            'initial_balance_cents' => $initial,
            'color' => $this->color,
            'closing_day' => $this->closing_day ?: null,
            'due_day' => $this->due_day ?: null,
            'archived' => $this->archived,
        ]);

        $this->showForm = false;
        session()->flash('status', 'Conta salva.');
    }

    public function delete(string $id): void
    {
        $account = Account::findOrFail($id);
        if ($account->transactions()->exists()) {
            $account->update(['archived' => true]);
            session()->flash('status', 'A conta possui lançamentos e foi arquivada.');
        } else {
            $account->delete();
            session()->flash('status', 'Conta excluída.');
        }
        $this->showForm = false;
    }

    public function render()
    {
        return view('livewire.accounts', [
            'accounts' => Account::orderBy('archived')->orderBy('name')->get()
                ->map(fn (Account $a) => ['account' => $a, 'balance' => $a->balanceCents()]),
        ]);
    }
}
