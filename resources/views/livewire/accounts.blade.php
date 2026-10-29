@php use App\Support\Money; use App\Models\Account; @endphp
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="page-title !mb-0">Contas</h1>
        <button wire:click="create" class="btn-primary ml-auto">+ Nova conta</button>
    </div>

    <div class="grid gap-3 md:grid-cols-2">
        @foreach ($accounts as $row)
            @php $a = $row['account']; @endphp
            <button wire:click="edit('{{ $a->id }}')" @class(['card flex items-center justify-between text-left', 'opacity-50' => $a->archived])>
                <div class="flex items-center gap-3">
                    <span class="h-10 w-2 rounded-full" style="background: {{ $a->color }}"></span>
                    <div>
                        <div class="font-semibold">{{ $a->name }}</div>
                        <div class="text-xs text-slate-500">{{ $a->typeLabel() }} @if ($a->archived) · arquivada @endif</div>
                    </div>
                </div>
                <div @class(['text-lg font-bold', 'text-expense' => $row['balance'] < 0, 'text-income' => $row['balance'] >= 0])>{{ Money::format($row['balance']) }}</div>
            </button>
        @endforeach
    </div>

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 md:items-center" wire:click.self="$set('showForm', false)">
            <form wire:submit="save" class="w-full space-y-3 rounded-t-2xl bg-white p-5 md:max-w-lg md:rounded-2xl dark:bg-slate-900">
                <h2 class="text-lg font-semibold">{{ $editingId ? 'Editar conta' : 'Nova conta' }}</h2>
                <div>
                    <label class="label">Nome</label>
                    <input type="text" wire:model="name" class="input">
                    @error('name') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="label">Tipo</label>
                        <select wire:model.live="type" class="input">
                            @foreach (Account::TYPES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">Saldo inicial (R$)</label>
                        <input type="text" inputmode="decimal" wire:model="initial_balance" class="input">
                        @error('initial_balance') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
                @if ($type === 'credit_card')
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="label">Dia de fechamento</label>
                            <input type="number" min="1" max="31" wire:model="closing_day" class="input">
                        </div>
                        <div>
                            <label class="label">Dia de vencimento</label>
                            <input type="number" min="1" max="31" wire:model="due_day" class="input">
                        </div>
                    </div>
                @endif
                <div class="flex items-center gap-4">
                    <div>
                        <label class="label">Cor</label>
                        <input type="color" wire:model="color" class="h-10 w-16 rounded">
                    </div>
                    <label class="mt-5 flex items-center gap-2 text-sm"><input type="checkbox" wire:model="archived"> Arquivada</label>
                </div>
                <div class="flex gap-2 pt-2">
                    @if ($editingId)
                        <button type="button" wire:click="delete('{{ $editingId }}')" wire:confirm="Excluir esta conta?" class="btn-danger">Excluir</button>
                    @endif
                    <button type="button" wire:click="$set('showForm', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" class="btn-primary flex-1">Salvar</button>
                </div>
            </form>
        </div>
    @endif
</div>

