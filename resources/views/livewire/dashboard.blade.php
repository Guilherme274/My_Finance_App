@php
    use App\Support\MaterialIcon;
    use App\Support\Money;
@endphp
<div class="flex flex-col w-full gap-space-lg" x-data="{ hidden: false }">
    {{-- Seletor rápido de mês --}}
    <div class="flex items-center justify-between bg-surface-container-low rounded-xl px-3 py-2 border border-outline-variant/20 animate-stagger-1">
        <button wire:click="shiftMonth(-1)" class="w-9 h-9 rounded-full bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-primary flex items-center justify-center tap-bounce">
            <span class="material-symbols-outlined text-[20px]">chevron_left</span>
        </button>
        <div class="flex items-center gap-1.5">
            <span class="material-symbols-outlined text-primary text-[18px]">calendar_month</span>
            <span class="text-title-md text-on-surface capitalize">{{ $monthLabel }}</span>
        </div>
        <button wire:click="shiftMonth(1)" class="w-9 h-9 rounded-full bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-primary flex items-center justify-center tap-bounce">
            <span class="material-symbols-outlined text-[20px]">chevron_right</span>
        </button>
    </div>

    {{-- Bloco Principal de Patrimônio Consolidado --}}
    <section class="relative overflow-hidden rounded-xl bg-surface-container-low p-space-md shadow-xl border border-primary/15 animate-stagger-1">
        <div class="absolute -top-12 -right-12 w-44 h-44 rounded-full bg-primary/10 blur-3xl pointer-events-none"></div>
        <div class="flex flex-col gap-space-sm relative z-10">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-space-xs">
                    <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                    <span class="text-label-sm uppercase tracking-wider text-on-surface-variant">Patrimônio Consolidado</span>
                </div>
                <button type="button" x-on:click="hidden = !hidden" aria-label="Alternar visibilidade do saldo"
                        class="w-8 h-8 rounded-full flex items-center justify-center bg-surface-container text-on-surface-variant hover:text-primary transition-colors tap-bounce">
                    <span class="material-symbols-outlined text-[18px]" x-text="hidden ? 'visibility_off' : 'visibility'">visibility</span>
                </button>
            </div>

            <div class="flex items-baseline justify-between mt-space-xs gap-2">
                <div class="flex flex-col">
                    <span x-show="!hidden" x-transition.opacity class="text-display-lg-mobile text-on-surface tracking-tight font-bold">
                        {{ Money::format($netWorthCents) }}
                    </span>
                    <span x-show="hidden" style="display: none;" class="text-display-lg-mobile text-on-surface tracking-tight font-bold">
                        R$ ••••••••
                    </span>
                    <span class="text-body-sm text-on-surface-variant mt-0.5" x-show="!hidden">
                        Contas: {{ Money::format($accountsTotal) }} · Carteira: {{ Money::format($investmentsTotal) }}
                    </span>
                </div>
                <div class="flex flex-col items-end shrink-0">
                    <span class="text-body-sm text-on-surface-variant mt-0.5">Saldo cadastrado</span>
                </div>
            </div>

            <div class="flex items-center justify-between pt-space-xs">
                <div class="flex items-center gap-1.5">
                    <span class="material-symbols-outlined text-primary text-[16px]">eco</span>
                    <span class="text-body-sm text-on-surface-variant">Rendimento estimado:</span>
                    <span x-show="!hidden" class="text-title-md text-income font-semibold">+{{ Money::format($monthlyYieldCents) }}</span>
                    <span x-show="hidden" style="display: none;" class="text-title-md text-income font-semibold">+R$ ••••</span>
                </div>
            </div>

            {{-- Micro Sparkline SVG minimalista animado --}}
            <div class="w-full h-11 mt-space-xs">
                <svg class="w-full h-full overflow-visible" fill="none" preserveAspectRatio="none" viewBox="0 0 320 44">
                    <defs>
                        <linearGradient id="glowGradient" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#4edea3" stop-opacity="0.35"></stop>
                            <stop offset="100%" stop-color="#4edea3" stop-opacity="0"></stop>
                        </linearGradient>
                    </defs>
                    <path d="M0,38 C35,36 50,28 80,30 C120,32 140,16 180,20 C220,24 250,8 285,12 C295,13 310,4 320,3 L320,44 L0,44 Z" fill="url(#glowGradient)"></path>
                    <path class="animate-sparkline" d="M0,38 C35,36 50,28 80,30 C120,32 140,16 180,20 C220,24 250,8 285,12 C295,13 310,4 320,3" stroke="#4edea3" stroke-linecap="round" stroke-width="2.5" vector-effect="non-scaling-stroke"></path>
                </svg>
            </div>
        </div>
    </section>

    {{-- Resumo Visual: Entradas vs Saídas --}}
    <section class="grid grid-cols-2 gap-space-sm animate-stagger-2">
        {{-- Card Entradas --}}
        <div class="rounded-xl bg-surface-container-low p-space-md flex flex-col justify-between shadow-md border border-outline-variant/20">
            <div class="flex items-center justify-between">
                <div class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
                </div>
                @if ($incomeDiffPct !== null)
                    <span class="text-label-sm text-primary font-semibold bg-primary/10 px-2 py-0.5 rounded-full">
                        {{ $incomeDiffPct >= 0 ? '+' : '' }}{{ $incomeDiffPct }}%
                    </span>
                @else
                    <span class="text-label-sm text-primary font-semibold bg-primary/10 px-2 py-0.5 rounded-full">Mês</span>
                @endif
            </div>
            <div class="mt-space-sm">
                <span class="text-body-sm text-on-surface-variant">Entradas</span>
                <div class="text-headline-md text-income font-bold mt-0.5" x-show="!hidden">{{ Money::format($income) }}</div>
                <div class="text-headline-md text-income font-bold mt-0.5" x-show="hidden" style="display: none;">R$ •••••</div>
            </div>
            <div class="w-full h-1 bg-surface-container rounded-full overflow-hidden mt-space-sm">
                <div class="h-full bg-income rounded-full animate-bar-x" style="width: {{ $incomeBarPct }}%;"></div>
            </div>
        </div>

        {{-- Card Saídas --}}
        <div class="rounded-xl bg-surface-container-low p-space-md flex flex-col justify-between shadow-md border border-outline-variant/20">
            <div class="flex items-center justify-between">
                <div class="w-7 h-7 rounded-lg bg-surface-container flex items-center justify-center text-error">
                    <span class="material-symbols-outlined text-[18px]">arrow_upward</span>
                </div>
                @if ($expenseDiffPct !== null)
                    <span @class([
                        'text-label-sm font-semibold px-2 py-0.5 rounded-full',
                        'text-error bg-error-container/20' => $expenseDiffPct > 0,
                        'text-primary bg-primary/10' => $expenseDiffPct <= 0,
                    ])>
                        {{ $expenseDiffPct > 0 ? '+' : '' }}{{ $expenseDiffPct }}%
                    </span>
                @else
                    <span class="text-label-sm text-error font-semibold bg-error-container/20 px-2 py-0.5 rounded-full">Mês</span>
                @endif
            </div>
            <div class="mt-space-sm">
                <span class="text-body-sm text-on-surface-variant">Saídas</span>
                <div class="text-headline-md text-expense font-bold mt-0.5" x-show="!hidden">{{ Money::format($expenseAbs) }}</div>
                <div class="text-headline-md text-expense font-bold mt-0.5" x-show="hidden" style="display: none;">R$ •••••</div>
            </div>
            <div class="w-full h-1 bg-surface-container rounded-full overflow-hidden mt-space-sm">
                <div class="h-full bg-expense rounded-full animate-bar-x" style="width: {{ $expenseBarPct }}%;"></div>
            </div>
        </div>
    </section>

    {{-- Reserva de Emergência Compacta --}}
    <section class="rounded-xl bg-surface-container-low p-space-md shadow-md border border-outline-variant/20 animate-stagger-3">
        <div class="flex items-center justify-between mb-space-xs">
            <div class="flex items-center gap-space-xs">
                <div class="w-8 h-8 rounded-lg bg-surface-container flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-[20px]">shield</span>
                </div>
                <div class="flex flex-col">
                    <span class="text-title-md text-on-surface font-semibold">Reserva de Emergência</span>
                    <span class="text-body-sm text-on-surface-variant">Meta: {{ Money::format($emergencyTarget) }}</span>
                </div>
            </div>
            <span class="text-title-md text-primary font-bold">{{ $emergencyPct }}%</span>
        </div>
        <div class="w-full h-2 bg-surface-container rounded-full overflow-hidden my-space-xs">
            <div class="h-full bg-gradient-to-r from-primary to-secondary-fixed rounded-full animate-bar-x" style="width: {{ $emergencyPct }}%;"></div>
        </div>
        <div class="flex items-center justify-between pt-1">
            <span class="text-body-sm text-on-surface-variant" x-show="!hidden">{{ Money::format($emergencyAccumulated) }} acumulados</span>
            <span class="text-body-sm text-on-surface-variant" x-show="hidden" style="display: none;">R$ ••••• acumulados</span>
            <a href="{{ route('investments') }}" wire:navigate class="text-label-md text-primary hover:underline flex items-center gap-0.5 tap-bounce">
                Detalhes
                <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            </a>
        </div>
    </section>

    {{-- Atalhos de Ação Rápida --}}
    <section class="flex flex-col gap-space-xs animate-stagger-4">
        <span class="text-label-sm uppercase tracking-wider text-on-surface-variant font-semibold">Ações Rápidas</span>
        <div class="grid grid-cols-4 gap-space-xs">
            <a href="{{ route('transactions.create', ['type' => 'expense']) }}" wire:navigate
               class="flex flex-col items-center justify-center p-space-sm rounded-xl bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/20 transition-all group tap-bounce">
                <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">add_circle</span>
                </div>
                <span class="text-label-sm text-on-surface-variant group-hover:text-on-surface mt-1.5 text-center leading-tight">Novo Gasto</span>
            </a>
            <a href="{{ route('transactions.create', ['type' => 'transfer']) }}" wire:navigate
               class="flex flex-col items-center justify-center p-space-sm rounded-xl bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/20 transition-all group tap-bounce">
                <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">swap_horiz</span>
                </div>
                <span class="text-label-sm text-on-surface-variant group-hover:text-on-surface mt-1.5 text-center leading-tight">Transferir</span>
            </a>
            <a href="{{ route('transactions', ['month' => $month]) }}" wire:navigate
               class="flex flex-col items-center justify-center p-space-sm rounded-xl bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/20 transition-all group tap-bounce">
                <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">receipt_long</span>
                </div>
                <span class="text-label-sm text-on-surface-variant group-hover:text-on-surface mt-1.5 text-center leading-tight">Extrato</span>
            </a>
            <a href="{{ route('import') }}" wire:navigate
               class="flex flex-col items-center justify-center p-space-sm rounded-xl bg-surface-container-low hover:bg-surface-container-high border border-outline-variant/20 transition-all group tap-bounce">
                <div class="w-10 h-10 rounded-full bg-surface-container flex items-center justify-center text-on-surface group-hover:scale-105 transition-transform">
                    <span class="material-symbols-outlined text-[20px]">sync_alt</span>
                </div>
                <span class="text-label-sm text-on-surface-variant group-hover:text-on-surface mt-1.5 text-center leading-tight">Importar</span>
            </a>
        </div>
    </section>

    {{-- Gastos por Categoria (Resumo do Mês) --}}
    <section class="rounded-xl bg-surface-container-low p-space-md shadow-md flex flex-col gap-space-sm border border-outline-variant/20 animate-stagger-5">
        <div class="flex items-center justify-between">
            <div class="flex flex-col">
                <span class="text-title-md text-on-surface font-semibold">Gastos por Categoria</span>
                <span class="text-body-sm text-expense">Total despendido: {{ Money::format($expenseAbs) }}</span>
            </div>
            <a href="{{ route('budget', ['month' => $month]) }}" wire:navigate class="text-label-md text-primary hover:underline tap-bounce">Ver todas</a>
        </div>

        <div class="flex flex-col gap-space-sm mt-space-xs">
            @forelse ($byCategory as $cat)
                <div class="flex flex-col gap-1">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-space-xs min-w-0">
                            <span class="w-2 h-2 rounded-full {{ $cat['barClass'] }} shrink-0"></span>
                            <span class="text-body-md text-on-surface truncate">{{ $cat['name'] }}</span>
                        </div>
                        <span class="text-body-md text-on-surface font-semibold shrink-0">
                            {{ $cat['pct'] }}%
                            <span class="text-body-sm text-expense font-normal">({{ Money::format($cat['total']) }})</span>
                        </span>
                    </div>
                    <div class="w-full h-1.5 bg-surface-container rounded-full overflow-hidden">
                        <div class="h-full {{ $cat['barClass'] }} rounded-full animate-bar-x" style="width: {{ max(4, $cat['pct']) }}%;"></div>
                    </div>
                </div>
            @empty
                <div class="py-4 text-center text-body-sm text-on-surface-variant">
                    Nenhuma despesa registrada neste mês.
                </div>
            @endforelse
        </div>
    </section>

    {{-- Feed de Últimas Transações --}}
    <section class="flex flex-col gap-space-xs animate-stagger-6">
        <div class="flex items-center justify-between px-space-xs">
            <span class="text-label-sm uppercase tracking-wider text-on-surface-variant font-semibold">Últimas Transações</span>
            <a class="text-label-md text-primary hover:underline tap-bounce" href="{{ route('transactions', ['month' => $month]) }}" wire:navigate>Ver Histórico</a>
        </div>

        <div class="rounded-xl bg-surface-container-low shadow-md overflow-hidden flex flex-col border border-outline-variant/20">
            @forelse ($recent as $index => $t)
                @if ($index > 0)
                    <div class="w-full h-px bg-surface-container-high/60 mx-space-md self-center"></div>
                @endif
                @php
                    $isIncome = $t->amount_cents >= 0;
                    $icon = $t->category
                        ? MaterialIcon::forCategory($t->category->name, $t->category->kind)
                        : ($isIncome ? 'trending_up' : 'receipt_long');

                    $parsedBrand = \App\Support\BankBrand::parse($t);
                    $bankBrand = $parsedBrand['brand'];
                    $cleanTitle = $parsedBrand['cleanDescription'];
                @endphp
                <a href="{{ route('transactions.create', ['editar' => $t->id]) }}" wire:navigate
                   class="flex items-center justify-between p-space-md hover:bg-surface-container transition-colors tap-bounce">
                    <div class="flex items-center gap-space-sm min-w-0">
                        <div @class([
                            'w-10 h-10 rounded-full flex items-center justify-center shrink-0',
                            'bg-primary/10 text-primary' => $isIncome,
                            'bg-surface-container text-on-surface-variant' => ! $isIncome,
                        ])>
                            <span class="material-symbols-outlined text-[20px]">{{ $icon }}</span>
                        </div>
                        <div class="flex flex-col min-w-0 truncate">
                            <div class="flex items-center gap-1.5 truncate">
                                @if ($bankBrand)
                                    <x-bank-icon :brand="$bankBrand" size="md" />
                                @endif
                                <span class="text-title-md text-on-surface truncate">{{ $cleanTitle }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span class="text-body-sm text-on-surface-variant">{{ $t->date->locale('pt_BR')->translatedFormat('d M') }}</span>
                                <span class="w-1 h-1 rounded-full bg-on-surface-variant/40"></span>
                                <span @class([
                                    'text-label-sm font-medium truncate',
                                    'text-primary' => $isIncome,
                                    'text-secondary' => ! $isIncome,
                                ])>{{ $t->category?->name ?? 'Sem categoria' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end shrink-0 ml-space-sm">
                        <span @class([
                            'text-title-md',
                            'text-income font-bold' => $isIncome,
                            'text-expense font-semibold' => ! $isIncome,
                        ]) x-show="!hidden">
                            {{ $isIncome ? '+' : '' }}{{ Money::format($t->amount_cents) }}
                        </span>
                        <span class="text-title-md text-on-surface font-semibold" x-show="hidden" style="display: none;">R$ ••••</span>
                        <span class="text-label-sm text-on-surface-variant">
                            {{ $t->account?->name }}
                            @if ($t->installments) · {{ $t->installment ?? 1 }}/{{ $t->installments }} @endif
                        </span>
                    </div>
                </a>
            @empty
                <div class="p-6 text-center text-body-sm text-on-surface-variant">
                    Nenhum lançamento neste mês.
                    <a href="{{ route('import') }}" wire:navigate class="text-primary hover:underline ml-1">Importar CSV</a>
                </div>
            @endforelse
        </div>
    </section>
</div>
