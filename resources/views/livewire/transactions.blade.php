@php
    use App\Support\MaterialIcon;
    use App\Support\Money;

    $allVisibleIds = collect($groupedTransactions)
        ->flatMap(fn ($g) => collect($g['items'])->pluck('id')->map(fn ($id) => (string) $id))
        ->values()
        ->all();
@endphp
<div
    x-data="{
        selectedMap: {},

        get selectedIds() {
            return Object.keys(this.selectedMap).filter(id => Boolean(this.selectedMap[id]));
        },

        get selectionCount() {
            return this.selectedIds.length;
        },

        isSelected(id) {
            return Boolean(this.selectedMap[String(id)]);
        },

        toggle(id) {
            id = String(id);
            if (this.selectedMap[id]) {
                delete this.selectedMap[id];
            } else {
                this.selectedMap[id] = true;
            }
        },

        isDaySelected(dayIds) {
            if (!dayIds || !dayIds.length) return false;
            return dayIds.every(id => Boolean(this.selectedMap[String(id)]));
        },

        toggleDay(dayIds) {
            if (!dayIds || !dayIds.length) return;
            const allSelected = this.isDaySelected(dayIds);
            dayIds.forEach(id => {
                const sid = String(id);
                if (allSelected) {
                    delete this.selectedMap[sid];
                } else {
                    this.selectedMap[sid] = true;
                }
            });
        },

        selectAll(allIds) {
            if (Array.isArray(allIds)) {
                allIds.forEach(id => {
                    this.selectedMap[String(id)] = true;
                });
            }
            $wire.selectAllFiltered();
        },

        clearAll() {
            this.selectedMap = {};
            $wire.clearSelection();
        },

        init() {
            const initIds = @js(array_values(array_unique(array_map('strval', $selectedIds))));
            initIds.forEach(id => {
                this.selectedMap[String(id)] = true;
            });
            this.$watch('$wire.selectedIds', (newVal) => {
                if (Array.isArray(newVal)) {
                    const next = {};
                    newVal.forEach(id => {
                        next[String(id)] = true;
                    });
                    this.selectedMap = next;
                }
            });
        }
    }"
    x-on:selection-count-updated.window="if (Array.isArray($event.detail?.ids)) { const next = {}; $event.detail.ids.forEach(id => { next[String(id)] = true; }); selectedMap = next; }"
    x-on:selection-reset.window="selectedMap = {}"
    class="flex flex-col w-full space-y-space-md animate-page-enter"
    :class="(selectionCount > 0 || $wire.bulkMode) ? 'pb-44' : 'pb-10'"
>
    {{-- 1. Mês & Navegação Rápida + Saldo Líquido + Pesquisa --}}
    <section class="flex flex-col w-full bg-surface-container-low rounded-xl p-space-md shadow-sm ghost-border animate-stagger-1">
        {{-- Seletor de Mês --}}
        <div class="flex items-center justify-between pb-space-sm">
            <button
                type="button"
                wire:click="shiftMonth(-1)"
                aria-label="Mês anterior"
                class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors tap-bounce"
            >
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </button>

            <div class="flex items-center gap-space-xs cursor-pointer select-none group" wire:click="toggleAdvancedFilters">
                <span class="material-symbols-outlined text-primary text-[18px]">calendar_today</span>
                <span class="font-title-md text-title-md text-on-surface font-semibold tracking-tight group-hover:text-primary transition-colors">
                    {{ $monthLabel }}
                </span>
                <span class="material-symbols-outlined text-on-surface-variant text-[18px]">expand_more</span>
            </div>

            <button
                type="button"
                wire:click="shiftMonth(1)"
                aria-label="Próximo mês"
                class="w-8 h-8 rounded-full flex items-center justify-center text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors tap-bounce"
            >
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </button>
        </div>

        {{-- Indicador de Saldo Líquido e Balanço --}}
        <div class="bg-surface-container rounded-lg p-space-md mt-space-xs flex flex-col gap-space-sm shadow-sm">
            <div class="flex items-center justify-between">
                <span class="font-label-sm text-label-sm text-on-surface-variant uppercase tracking-wider">Saldo Líquido</span>
                <span class="px-space-xs py-0.5 rounded {{ $netCents >= 0 ? 'bg-primary-container/15 text-primary' : 'bg-error/15 text-error' }} font-label-sm text-label-sm font-semibold flex items-center gap-1 tabular-nums">
                    <span class="material-symbols-outlined text-[13px]">{{ $netCents >= 0 ? 'trending_up' : 'trending_down' }}</span>
                    {{ $netPct >= 0 ? '+' : '' }}{{ number_format($netPct, 1, ',', '.') }}%
                </span>
            </div>

            <div class="flex items-baseline justify-between gap-2 flex-wrap">
                <span class="font-display-lg-mobile text-display-lg-mobile font-bold tracking-tight tabular-nums {{ $netCents >= 0 ? 'text-income' : 'text-expense' }}">
                    {{ $netCents >= 0 ? '+' : '-' }}R$ {{ number_format(abs($netCents) / 100, 2, ',', '.') }}
                </span>
                <span class="font-body-sm text-body-sm text-on-surface-variant tabular-nums">
                    {{ $totalFilteredCount }} {{ $totalFilteredCount === 1 ? 'registro' : 'registros' }}
                </span>
            </div>

            {{-- Métricas Entradas / Saídas --}}
            <div class="grid grid-cols-2 gap-space-sm pt-space-xs">
                <div class="flex flex-col bg-surface-container-high rounded-lg p-space-sm">
                    <div class="flex items-center gap-1.5 text-income">
                        <span class="material-symbols-outlined text-[16px]">arrow_downward</span>
                        <span class="font-label-sm text-label-sm uppercase font-semibold">Entradas</span>
                    </div>
                    <span class="font-title-md text-title-md text-income font-bold mt-0.5 tabular-nums">
                        +R$ {{ number_format($incomeCents / 100, 2, ',', '.') }}
                    </span>
                    <span class="font-label-sm text-label-sm text-on-surface-variant mt-0.5">
                        {{ $incomeCount }} {{ $incomeCount === 1 ? 'receita' : 'receitas' }}
                    </span>
                </div>

                <div class="flex flex-col bg-surface-container-high rounded-lg p-space-sm">
                    <div class="flex items-center gap-1.5 text-expense">
                        <span class="material-symbols-outlined text-[16px]">arrow_upward</span>
                        <span class="font-label-sm text-label-sm uppercase font-semibold">Saídas</span>
                    </div>
                    <span class="font-title-md text-title-md text-expense font-bold mt-0.5 tabular-nums">
                        -R$ {{ number_format(abs($expenseCents) / 100, 2, ',', '.') }}
                    </span>
                    <span class="font-label-sm text-label-sm text-on-surface-variant mt-0.5">
                        {{ $expenseCount }} {{ $expenseCount === 1 ? 'despesa' : 'despesas' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Barra de Pesquisa Compacta + Botão Edição em Massa + Filtro --}}
        <div class="mt-space-md flex items-center gap-space-sm">
            <div class="relative flex-1 flex items-center">
                <span class="material-symbols-outlined absolute left-3 text-on-surface-variant text-[20px] pointer-events-none">search</span>
                <input
                    type="search"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Descrição, conta ou tag..."
                    class="w-full bg-surface-container-highest/60 text-on-surface placeholder:text-on-surface-variant/70 text-body-sm font-body-sm rounded-lg pl-10 pr-4 py-2.5 outline-none focus:bg-surface-container-highest border border-transparent focus:border-primary/40 transition-all"
                />
            </div>

            <button
                type="button"
                wire:click="toggleBulkMode"
                title="Selecionar vários para editar categoria em massa"
                class="h-10 px-3 rounded-lg flex items-center gap-1.5 text-xs transition-all flex-shrink-0 tap-bounce"
                :class="(selectionCount > 0 || $wire.bulkMode) ? 'bg-primary text-on-primary font-bold shadow-[0_0_16px_rgba(78,222,163,0.35)]' : 'bg-surface-container-highest/60 text-on-surface-variant hover:text-primary hover:bg-surface-container-highest'"
            >
                <span class="material-symbols-outlined text-[19px]">checklist</span>
                <span class="hidden sm:inline" x-text="$wire.bulkMode ? 'Sair da Seleção' : 'Editar em Massa'">{{ $bulkMode ? 'Sair da Seleção' : 'Editar em Massa' }}</span>
                <span x-cloak x-show="selectionCount > 0" class="px-1.5 py-0.2 rounded-full bg-on-primary/20 text-[11px] font-extrabold tabular-nums" x-text="selectionCount">{{ count($selectedIds) }}</span>
            </button>

            <button
                type="button"
                wire:click="toggleAdvancedFilters"
                aria-label="Opções avançadas de filtro"
                class="w-10 h-10 rounded-lg {{ $showAdvancedFilters || $category !== '' ? 'bg-primary/20 text-primary border border-primary/30' : 'bg-surface-container-highest/60 text-on-surface-variant' }} hover:bg-surface-container-highest flex items-center justify-center hover:text-primary transition-colors flex-shrink-0 tap-bounce"
            >
                <span class="material-symbols-outlined text-[20px]">tune</span>
            </button>
        </div>

        {{-- Gaveta de Filtros Avançados (Categoria, Conta e Mês Exato) --}}
        @if ($showAdvancedFilters)
            <div class="mt-3 pt-3 border-t border-outline-variant/20 grid grid-cols-1 sm:grid-cols-3 gap-2.5 animate-pop">
                <div>
                    <label class="text-[0.65rem] font-bold uppercase tracking-wider text-on-surface-variant block mb-1">Mês / Período</label>
                    <input type="month" wire:model.live="month" class="input !py-2 text-xs font-semibold" />
                </div>
                <div>
                    <label class="text-[0.65rem] font-bold uppercase tracking-wider text-on-surface-variant block mb-1">Conta</label>
                    <select wire:model.live="account" class="input !py-2 text-xs font-semibold">
                        <option value="">Todas as contas</option>
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-[0.65rem] font-bold uppercase tracking-wider text-on-surface-variant block mb-1">Categoria</label>
                    <select wire:model.live="category" class="input !py-2 text-xs font-semibold">
                        <option value="">Todas as categorias</option>
                        <option value="none">Sem categoria</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif
    </section>

    {{-- 2. Chips de Filtros Rápidos com Scroll Suave --}}
    <section class="flex items-center gap-space-xs overflow-x-auto no-scrollbar py-0.5 -mx-margin px-margin animate-stagger-2">
        @php
            $isAllActive = $typeFilter === 'all' && $account === '' && $category === '';
        @endphp
        <button
            type="button"
            wire:click="setQuickFilter('all')"
            class="px-space-md py-1.5 rounded-full font-label-md text-label-md font-semibold whitespace-nowrap shadow-sm flex items-center gap-1.5 flex-shrink-0 transition-all tap-bounce
                {{ $isAllActive ? 'bg-primary text-on-primary' : 'bg-surface-container-low hover:bg-surface-container-high text-on-surface ghost-border' }}"
        >
            <span>Todos</span>
            <span class="{{ $isAllActive ? 'bg-on-primary/20 text-on-primary' : 'bg-surface-container-highest text-on-surface-variant' }} px-1.5 py-0.2 rounded-full text-label-sm tabular-nums">
                {{ $allMonthCount }}
            </span>
        </button>

        @if ($uncategorizedMonthCount > 0)
            <button
                type="button"
                wire:click="setQuickFilter('uncategorized')"
                class="px-space-md py-1.5 rounded-full font-label-md text-label-md whitespace-nowrap transition-all flex items-center gap-1.5 flex-shrink-0 tap-bounce
                    {{ $typeFilter === 'uncategorized' ? 'bg-primary text-on-primary font-semibold shadow-sm' : 'bg-surface-container-low hover:bg-surface-container-high text-on-surface ghost-border' }}"
            >
                <span class="material-symbols-outlined text-[15px]">help</span>
                <span>Sem Categoria</span>
                <span class="{{ $typeFilter === 'uncategorized' ? 'bg-on-primary/20 text-on-primary' : 'bg-primary/15 text-primary' }} px-1.5 py-0.2 rounded-full text-label-sm font-bold tabular-nums">
                    {{ $uncategorizedMonthCount }}
                </span>
            </button>
        @endif

        <button
            type="button"
            wire:click="setQuickFilter('income')"
            class="px-space-md py-1.5 rounded-full font-label-md text-label-md whitespace-nowrap transition-all flex items-center gap-1.5 flex-shrink-0 tap-bounce
                {{ $typeFilter === 'income' ? 'bg-primary text-on-primary font-semibold shadow-sm' : 'bg-surface-container-low hover:bg-surface-container-high text-on-surface ghost-border' }}"
        >
            <span class="w-2 h-2 rounded-full {{ $typeFilter === 'income' ? 'bg-on-primary' : 'bg-primary' }}"></span>
            <span>Receitas</span>
        </button>

        <button
            type="button"
            wire:click="setQuickFilter('expense')"
            class="px-space-md py-1.5 rounded-full font-label-md text-label-md whitespace-nowrap transition-all flex items-center gap-1.5 flex-shrink-0 tap-bounce
                {{ $typeFilter === 'expense' ? 'bg-error text-on-error font-semibold shadow-sm' : 'bg-surface-container-low hover:bg-surface-container-high text-on-surface ghost-border' }}"
        >
            <span class="w-2 h-2 rounded-full {{ $typeFilter === 'expense' ? 'bg-on-error' : 'bg-error' }}"></span>
            <span>Despesas</span>
        </button>

        @foreach ($accounts as $acc)
            @php
                $isAccSelected = $account === $acc->id;
                $accIcon = $acc->type === 'credit_card' ? 'credit_card' : 'account_balance_wallet';
            @endphp
            <button
                type="button"
                wire:click="toggleAccountFilter('{{ $acc->id }}')"
                class="px-space-md py-1.5 rounded-full font-label-md text-label-md whitespace-nowrap transition-all flex-shrink-0 flex items-center gap-1.5 tap-bounce
                    {{ $isAccSelected ? 'bg-primary text-on-primary font-semibold shadow-sm' : 'bg-surface-container-low hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface ghost-border' }}"
            >
                <span class="material-symbols-outlined text-[15px]">{{ $accIcon }}</span>
                <span>{{ $acc->name }}</span>
            </button>
        @endforeach

        <button
            type="button"
            wire:click="setQuickFilter('pix')"
            class="px-space-md py-1.5 rounded-full font-label-md text-label-md whitespace-nowrap transition-all flex-shrink-0 flex items-center gap-1 tap-bounce
                {{ $typeFilter === 'pix' ? 'bg-primary text-on-primary font-semibold shadow-sm' : 'bg-surface-container-low hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface ghost-border' }}"
        >
            <span class="material-symbols-outlined text-[15px]">bolt</span>
            <span>Pix</span>
        </button>
    </section>

    {{-- Banner Auxiliar quando Modo de Seleção em Massa está ativo --}}
    <div
        x-cloak
        x-show="selectionCount > 0 || $wire.bulkMode"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-2"
        class="bg-primary/10 border border-primary/30 rounded-xl px-4 py-2.5 flex items-center justify-between flex-wrap gap-2"
    >
            <div class="flex items-center gap-2 text-xs font-semibold text-on-surface">
                <span class="material-symbols-outlined text-primary text-lg">checklist</span>
                <span>
                    Use as caixas para selecionar lançamentos
                    (<strong class="text-primary tabular-nums" x-text="selectionCount">{{ count($selectedIds) }}</strong> de <strong class="tabular-nums">{{ $totalFilteredCount }}</strong> selecionados).
                </span>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    x-on:click.prevent="selectAll(@js($allVisibleIds))"
                    class="px-3 py-1 rounded-lg bg-primary/20 hover:bg-primary/30 text-primary text-xs font-bold transition-colors tap-bounce"
                >
                    Selecionar todos ({{ $totalFilteredCount }})
                </button>
                <button
                    type="button"
                    x-on:click.prevent="clearAll()"
                    x-show="selectionCount > 0"
                    class="px-2.5 py-1 rounded-lg bg-surface-container-high hover:bg-surface-container-highest text-on-surface-variant text-xs font-semibold transition-colors"
                >
                    Limpar
                </button>
            </div>
        </div>

    {{-- 3. Lista de Transações Agrupada por Data --}}
    <div class="flex flex-col space-y-space-md w-full animate-stagger-3">
        @forelse ($groupedTransactions as $group)
            @php
                $groupTxIds = collect($group['items'])->pluck('id')->map(fn ($id) => (string) $id)->all();
                $isAllDaySelected = count($groupTxIds) > 0 && count(array_intersect($groupTxIds, $selectedIds)) === count($groupTxIds);
            @endphp
            <section class="flex flex-col w-full" wire:key="group-{{ $group['date_str'] }}">
                {{-- Cabeçalho do Dia --}}
                <div class="flex items-center justify-between py-space-xs px-1">
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            x-cloak
                            x-show="selectionCount > 0 || $wire.bulkMode"
                            x-on:click.prevent.stop="toggleDay(@js($groupTxIds))"
                            class="w-5 h-5 rounded-md flex items-center justify-center transition-all border"
                            :class="isDaySelected(@js($groupTxIds)) ? 'bg-primary border-primary text-on-primary shadow-[0_0_8px_rgba(78,222,163,0.4)]' : 'bg-surface-container-high border-outline-variant/50 text-transparent hover:border-primary'"
                            title="Selecionar todos deste dia"
                        >
                            <span class="material-symbols-outlined text-[15px] font-bold">check</span>
                        </button>
                        <span class="font-title-md text-title-md font-semibold text-on-surface">
                            {{ $group['label'] }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2.5">
                        <button
                            type="button"
                            x-show="selectionCount === 0 && ! $wire.bulkMode"
                            x-on:click.prevent.stop="toggleDay(@js($groupTxIds))"
                            class="text-[11px] font-semibold text-on-surface-variant/70 hover:text-primary transition-colors hidden sm:inline"
                        >
                            Selecionar dia
                        </button>
                        <span class="font-label-md text-label-md font-medium tabular-nums {{ $group['net_cents'] >= 0 ? 'text-income' : 'text-expense' }}">
                            {{ $group['net_cents'] >= 0 ? '+' : '-' }}R$ {{ number_format(abs($group['net_cents']) / 100, 2, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- Card de Itens do Dia --}}
                <div class="flex flex-col bg-surface-container-low rounded-xl overflow-hidden mt-space-xs ghost-border">
                    @foreach ($group['items'] as $idx => $t)
                        @if ($idx > 0)
                            <div class="h-[1px] bg-surface-container-high/50 mx-space-md"></div>
                        @endif

                        @php
                            $txIdStr = (string) $t->id;
                            $isSelected = in_array($txIdStr, $selectedIds, true);
                            $isIncome = $t->amount_cents >= 0;

                            // 1. Ícone fiel da categoria (exatamente como na tela completa de edição do lançamento)
                            $catIcon = $t->category
                                ? MaterialIcon::forCategory($t->category->name, $t->category->kind)
                                : ($isIncome ? 'trending_up' : 'receipt_long');

                            // 2. Reconhecimento de marca bancária (Mercado Pago / Nubank) no lugar do prefixo "MP"
                            $parsedBrand = \App\Support\BankBrand::parse($t);
                            $bankBrand = $parsedBrand['brand'];
                            $cleanTitle = $parsedBrand['cleanDescription'];

                            $descUpper = mb_strtoupper($t->description);
                            $isYield = str_contains($descUpper, 'RENDIMENTO') || str_contains($descUpper, 'DIVIDENDO') || str_contains($descUpper, 'CDI');
                            $isSameOwner = str_contains($descUpper, 'PRÓPRIA TITULARIDADE');
                            $isPix = str_contains($descUpper, 'PIX');

                            if ($t->installments) {
                                $methodLabel = ($t->installment ?? 1).'/'.$t->installments.'x';
                            } elseif ($isYield) {
                                $methodLabel = 'Provento CDI';
                            } elseif ($isPix) {
                                $methodLabel = $isIncome ? 'Pix Recebido' : 'Pix Enviado';
                            } elseif ($t->account?->type === 'credit_card') {
                                $methodLabel = 'Cartão';
                            } else {
                                $methodLabel = $isIncome ? 'Entrada' : 'Débito';
                            }

                            $timeLabel = $t->created_at ? $t->created_at->format('H:i') : '09:00';
                        @endphp

                        <div
                            wire:key="tx-{{ $t->id }}"
                            class="p-space-md flex items-center gap-space-md transition-colors group"
                            :class="isSelected('{{ $txIdStr }}') ? 'bg-primary/10 hover:bg-primary/15' : 'hover:bg-surface-container'"
                        >
                            {{-- Checkbox de Seleção em Massa --}}
                            <input
                                type="checkbox"
                                value="{{ $txIdStr }}"
                                :checked="isSelected('{{ $txIdStr }}')"
                                @click.stop="toggle('{{ $txIdStr }}')"
                                aria-label="Selecionar lançamento: {{ $cleanTitle }}"
                                class="transaction-select w-5 h-5 flex-shrink-0 rounded border-outline-variant text-primary focus:ring-primary bg-surface-container-high cursor-pointer"
                                style="accent-color: #4edea3"
                            >

                            <a
                                href="{{ route('transactions.create', ['editar' => $t->id]) }}"
                                wire:navigate
                                x-on:click="if (selectionCount > 0 || $wire.bulkMode) { $event.preventDefault(); toggle('{{ $txIdStr }}'); }"
                                class="flex flex-1 items-center justify-between min-w-0 cursor-pointer"
                            >
                                <div class="flex items-center gap-space-md min-w-0">
                                    <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0 shadow-sm transition-colors
                                        {{ $isIncome
                                            ? 'bg-primary-container/20 text-primary'
                                            : 'bg-surface-container-high text-on-surface-variant group-hover:text-primary' }}">
                                        <span class="material-symbols-outlined text-[22px]">{{ $catIcon }}</span>
                                    </div>

                                    <div class="flex flex-col min-w-0">
                                        <div class="flex items-center gap-2">
                                            @if ($bankBrand)
                                                <x-bank-icon :brand="$bankBrand" size="lg" />
                                            @endif
                                            <span class="font-title-md text-title-md text-on-surface font-semibold truncate leading-snug group-hover:text-primary transition-colors">
                                                {{ $cleanTitle }}
                                            </span>
                                            @if ($isYield)
                                                <span class="px-1.5 py-0.2 rounded bg-primary-container/15 text-primary text-[10px] font-semibold tracking-wide uppercase shrink-0">
                                                    Rendimento
                                                </span>
                                            @elseif ($isSameOwner)
                                                <span class="px-1.5 py-0.2 rounded bg-primary-container/15 text-primary text-[10px] font-semibold tracking-wide uppercase shrink-0 hidden sm:inline">
                                                    Própria Titularidade
                                                </span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-1.5 text-on-surface-variant font-body-sm text-body-sm truncate mt-0.5">
                                            <span class="flex items-center gap-1 truncate {{ ! $t->category ? 'text-secondary-fixed-dim font-medium' : '' }}">
                                                <span class="material-symbols-outlined text-[14px] shrink-0 {{ $isIncome ? 'text-primary' : 'text-on-surface-variant' }}">{{ $catIcon }}</span>
                                                <span class="truncate">{{ $t->category?->name ?? 'Sem categoria' }}</span>
                                            </span>
                                            <span class="text-outline-variant">•</span>
                                            <span class="truncate">
                                                {{ $t->account?->name ?? 'Conta' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end flex-shrink-0 ml-space-sm text-right">
                                    <span class="font-title-md text-title-md font-bold tracking-tight tabular-nums {{ $isIncome ? 'text-income' : 'text-expense' }}">
                                        {{ $isIncome ? '+' : '-' }}R$ {{ number_format(abs($t->amount_cents) / 100, 2, ',', '.') }}
                                    </span>
                                    <div class="flex items-center gap-1 text-on-surface-variant font-label-sm text-label-sm mt-0.5">
                                        <span class="{{ $t->installments ? 'text-secondary font-medium' : '' }}">{{ $methodLabel }}</span>
                                        <span>•</span>
                                        <span class="tabular-nums">{{ $timeLabel }}</span>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="bg-surface-container-low rounded-xl p-8 ghost-border text-center space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-surface-container-high text-on-surface-variant flex items-center justify-center mx-auto">
                    <span class="material-symbols-outlined text-2xl">receipt_long</span>
                </div>
                <div>
                    <p class="text-sm font-bold text-on-surface">Nenhum lançamento encontrado neste período</p>
                    <p class="text-xs text-on-surface-variant mt-0.5">Tente ajustar os filtros ou selecione outro mês nas setas acima.</p>
                </div>
                <button
                    type="button"
                    wire:click="create"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary/15 text-primary text-xs font-bold hover:bg-primary/25 transition-colors tap-bounce"
                >
                    <span class="material-symbols-outlined text-base">add</span>
                    Novo Lançamento
                </button>
            </div>
        @endforelse
    </div>

    {{-- 4. Rodapé com Contador e Ação de Exportação --}}
    <footer class="pt-space-md flex flex-col items-center gap-space-md animate-stagger-4">
        <div class="flex items-center gap-2 text-on-surface-variant font-label-sm text-label-sm">
            <span class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"></span>
            <span>Exibindo {{ $transactions->count() }} de {{ $totalFilteredCount }} lançamentos deste período</span>
        </div>

        @if ($transactions->hasMorePages())
            <button
                type="button"
                wire:click="loadMore"
                class="px-5 py-2 rounded-xl bg-primary/15 hover:bg-primary/25 text-primary text-xs font-bold transition-colors tap-bounce"
            >
                Carregar mais lançamentos ({{ $totalFilteredCount - $transactions->count() }} restantes)
            </button>
        @endif

        {{-- Botão de Ação Secundária / Exportação --}}
        <button
            type="button"
            wire:click="exportCsv"
            class="w-full py-3.5 px-space-md rounded-xl bg-surface-container hover:bg-surface-container-high text-on-surface font-title-md text-title-md font-semibold flex items-center justify-center gap-space-sm transition-all shadow-md group ghost-border tap-bounce"
        >
            <span class="material-symbols-outlined text-[20px] text-primary group-hover:scale-110 transition-transform">ios_share</span>
            <span>Exportar Extrato (OFX / PDF / CSV)</span>
        </button>
    </footer>

    {{-- 5. Barra Flutuante de Edição de Categoria em Massa --}}
    <div
        x-cloak
        x-show="selectionCount > 0 || $wire.bulkMode"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-6 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-6 scale-95"
        class="fixed bottom-20 lg:bottom-6 left-1/2 -translate-x-1/2 z-50 w-[95%] max-w-2xl rounded-2xl bg-surface-container-highest/95 backdrop-blur-xl border border-primary/40 p-4 shadow-[0_16px_48px_rgba(0,0,0,0.65)] space-y-3"
    >
            <div class="flex items-center justify-between gap-2 flex-wrap">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-primary text-on-primary text-xs font-extrabold tabular-nums">
                        <span x-text="selectionCount">{{ count($selectedIds) }}</span> selecionado(s)
                    </span>
                    <button
                        type="button"
                        x-on:click.prevent="selectAll(@js($allVisibleIds))"
                        class="text-xs font-bold text-primary hover:underline"
                    >
                        Selecionar todos do filtro ({{ $totalFilteredCount }})
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-1.5 text-xs text-on-surface-variant cursor-pointer select-none">
                        <input
                            type="checkbox"
                            wire:model="bulkApplySameDescription"
                            class="rounded border-outline-variant text-primary focus:ring-primary bg-surface-container"
                        />
                        <span>Aplicar à mesma descrição em todos os meses</span>
                    </label>
                    <button
                        type="button"
                        x-on:click="clearAll()"
                        class="text-xs font-semibold text-on-surface-variant hover:text-on-surface"
                    >
                        Fechar
                    </button>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <select wire:model="bulkCategoryId" class="input !py-2.5 text-xs font-bold flex-1 bg-surface-container-low">
                    <option value="">Selecione a categoria para aplicar em massa...</option>
                    <option value="__none__">Sem categoria (remover categoria)</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}">
                            {{ $c->name }} ({{ $c->kind === 'income' ? 'Receita' : 'Despesa' }})
                        </option>
                    @endforeach
                </select>

                <button
                    type="button"
                    wire:click="applyBulkCategory(selectedIds)"
                    x-on:click="$wire.set('selectedIds', selectedIds)"
                    wire:loading.attr="disabled"
                    class="btn-primary !py-2.5 !px-5 text-xs font-extrabold shrink-0 tap-bounce"
                >
                    <span class="material-symbols-outlined text-base">done_all</span>
                    <span>Aplicar Categoria</span>
                </button>
            </div>
        </div>
</div>
