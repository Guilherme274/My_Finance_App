@php use App\Support\Money; use App\Models\Category; @endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h1 class="page-title !mb-0">Categorias</h1>
        <div class="ml-auto flex gap-2">
            @if ($uncategorized)
                <button wire:click="applyRules" class="btn-secondary">⚡ Categorizar {{ $uncategorized }} sem categoria</button>
            @endif
            <button wire:click="create" class="btn-primary">+ Nova</button>
        </div>
    </div>

    @foreach (Category::KINDS as $kind => $label)
        @if ($groups->has($kind))
            <div>
                <h2 class="mb-2 text-sm font-semibold text-slate-500">{{ $label }}</h2>
                <div class="grid gap-2 md:grid-cols-3">
                    @foreach ($groups[$kind] as $c)
                        @php
                            $catIcon = \App\Support\MaterialIcon::forCategory($c->name, $c->kind);
                        @endphp
                        <button wire:click="edit('{{ $c->id }}')" class="card flex items-center justify-between !py-3 text-left">
                            <span class="flex items-center gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl shadow-sm" style="background: {{ $c->color }}22; color: {{ $c->color }};">
                                    <span class="material-symbols-outlined text-[20px]">{{ $catIcon }}</span>
                                </span>
                                <span>
                                    <span class="block text-sm font-semibold">{{ $c->name }}</span>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <span class="text-xs text-slate-400">{{ $c->transactions_count }} lançamento(s)</span>
                                        @if (! $c->include_in_budget)
                                            <span class="rounded bg-slate-800/80 px-1.5 py-0.5 text-[10px] font-semibold text-slate-400 border border-slate-700/50">Fora do orçamento</span>
                                        @elseif ($c->kind === 'expense')
                                            <span class="rounded bg-slate-800/60 px-1.5 py-0.5 text-[10px] font-semibold text-primary/80 border border-primary/20">
                                                {{ $c->getAllocationGroup() === 'essential' ? '50% Essencial' : ($c->getAllocationGroup() === 'investment' ? '20% Invest.' : '30% Estilo') }}
                                            </span>
                                        @endif
                                    </div>
                                </span>
                            </span>
                            @if ($c->monthly_budget_percent !== null)
                                <span class="text-xs text-slate-500">{{ number_format($c->monthly_budget_percent, 1, ',', '.') }}% da renda</span>
                            @elseif ($c->monthly_budget_cents)
                                <span class="text-xs text-slate-500">{{ Money::format($c->monthly_budget_cents) }}/mês</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach

    @if ($showForm)
        <div class="fixed inset-0 z-40 flex items-end justify-center bg-black/40 md:items-center" wire:click.self="$set('showForm', false)">
            <div class="max-h-[90vh] w-full space-y-3 overflow-y-auto rounded-t-2xl bg-white p-5 md:max-w-lg md:rounded-2xl dark:bg-slate-900">
                <form wire:submit="save" class="space-y-3">
                    <h2 class="text-lg font-semibold">{{ $editingId ? 'Editar categoria' : 'Nova categoria' }}</h2>
                    <div class="grid grid-cols-4 gap-2">
                        <div>
                            <label class="label">Ícone</label>
                            <div class="flex items-center gap-1.5">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-primary shrink-0 border border-slate-300 dark:border-slate-700">
                                    <span class="material-symbols-outlined text-[20px]">{{ \App\Support\MaterialIcon::forCategory($name ?: 'geral', $kind) }}</span>
                                </span>
                                <input type="text" wire:model.live="icon" placeholder="{{ \App\Support\MaterialIcon::forCategory($name ?: 'geral', $kind) }}" class="input !py-2 text-xs font-mono" maxlength="30">
                            </div>
                        </div>
                        <div class="col-span-3">
                            <label class="label">Nome</label>
                            <input type="text" wire:model.live="name" class="input">
                            @error('name') <p class="error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div>
                            <label class="label">Tipo</label>
                            <select wire:model="kind" class="input">
                                @foreach (Category::KINDS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">Limite fixo por mês (R$)</label>
                            <input type="text" inputmode="decimal" wire:model="budget" placeholder="—" class="input">
                            @error('budget') <p class="error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Ou percentual da renda mensal</label>
                            <input type="number" min="0.01" max="100" step="0.01" inputmode="decimal" wire:model="budgetPercent" placeholder="ex.: 15" class="input">
                            @error('budgetPercent') <p class="error">Informe um percentual entre 0 e 100.</p> @enderror
                        </div>
                        <div>
                            <label class="label">Regra 50 / 30 / 20</label>
                            <select wire:model="budgetAllocation" class="input">
                                <option value="">Automático</option>
                                <option value="essential">50% · Essenciais</option>
                                <option value="lifestyle">30% · Estilo de Vida</option>
                                <option value="investment">20% · Investimentos / Futuro</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Cor</label>
                            <input type="color" wire:model="color" class="h-10 w-full rounded">
                        </div>
                    </div>
                    <div class="rounded-xl bg-slate-100 p-3 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700/50">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input type="checkbox" wire:model="includeInBudget" class="rounded border-slate-400 dark:border-slate-600 bg-white dark:bg-slate-700 text-primary focus:ring-primary h-4 w-4">
                            <span class="text-xs font-bold text-slate-800 dark:text-slate-100">Entrar no orçamento e gráficos gerais</span>
                        </label>
                        <p class="text-[0.6875rem] text-slate-500 dark:text-slate-400 mt-1 ml-6 leading-relaxed">
                            Mantenha marcado para categorias normais de gastos e receitas. Desmarque para transferências entre contas próprias ou pagamento de fatura, evitando falsa impressão de gastos duplicados.
                        </p>
                    </div>
                    <div class="flex gap-2">
                        @if ($editingId)
                            <button type="button" wire:click="delete('{{ $editingId }}')" wire:confirm="Excluir a categoria? Os lançamentos ficarão sem categoria." class="btn-danger">Excluir</button>
                        @endif
                        <button type="button" wire:click="$set('showForm', false)" class="btn-secondary">Fechar</button>
                        <button type="submit" class="btn-primary flex-1">Salvar</button>
                    </div>
                </form>

                <div class="border-t border-slate-200 pt-3 dark:border-slate-700">
                    <h3 class="mb-1 text-sm font-semibold">Regras de categorização automática</h3>
                    <p class="mb-2 text-xs text-slate-500">Se a descrição contiver o texto, o lançamento recebe esta categoria (na importação de CSV e no botão ⚡).</p>
                    <div class="mb-2 flex flex-wrap gap-1">
                        @forelse ($rules as $rule)
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-xs dark:bg-slate-800">
                                {{ $rule->pattern }}
                                <button wire:click="removeRule('{{ $rule->id }}')" class="text-slate-400 hover:text-rose-600">×</button>
                            </span>
                        @empty
                            <span class="text-xs text-slate-400">Nenhuma regra.</span>
                        @endforelse
                    </div>
                    <form wire:submit="addRule" class="flex gap-2">
                        <input type="text" wire:model="newPattern" placeholder="ex.: ifood" class="input">
                        <button class="btn-secondary">Adicionar</button>
                    </form>
                    @error('newPattern') <p class="error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>
    @endif
</div>

