<div class="space-y-6 animate-page-enter">
    <!-- Top Section: Month Selector & Filter Pills -->
    <section class="pt-1 flex flex-col gap-4 animate-stagger-1">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[0.6875rem] uppercase tracking-[0.08em] text-on-surface-variant font-semibold">Visão Mensal</p>
                <div class="flex items-center gap-2 mt-0.5">
                    <h2 class="text-headline-md font-extrabold tracking-tight text-on-surface">{{ $monthLabel }}</h2>
                    <div class="flex items-center gap-1 ml-1">
                        <button type="button" wire:click="previousMonth" class="w-8 h-8 rounded-lg bg-surface-container-low hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors tap-bounce" title="Mês anterior">
                            <span class="material-symbols-outlined text-lg">chevron_left</span>
                        </button>
                        <button type="button" wire:click="nextMonth" class="w-8 h-8 rounded-lg bg-surface-container-low hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors tap-bounce" title="Próximo mês">
                            <span class="material-symbols-outlined text-lg">chevron_right</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filter Pills -->
            <div class="flex items-center gap-1.5 bg-surface-container-low p-1 rounded-xl ghost-border">
                <button type="button" wire:click="setFilter('expenses')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all tap-bounce {{ $filter === 'expenses' ? 'bg-primary/15 text-primary font-bold shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}">
                    Despesas
                </button>
                <button type="button" wire:click="setFilter('income')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all tap-bounce {{ $filter === 'income' ? 'bg-primary/15 text-primary font-bold shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}">
                    Entradas
                </button>
                <button type="button" wire:click="setFilter('invoices')"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all tap-bounce {{ $filter === 'invoices' ? 'bg-primary/15 text-primary font-bold shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}">
                    Faturas
                </button>
            </div>
        </div>

        <!-- Monthly Summary Mini-Bar -->
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-surface-container-low rounded-xl p-4 ghost-border flex items-center justify-between hover-lift">
                <div>
                    <span class="text-[0.6875rem] uppercase tracking-wider text-on-surface-variant block">
                        {{ $filter === 'income' ? 'Total Recebido' : ($filter === 'invoices' ? 'Total Faturas' : 'Total Acumulado') }}
                    </span>
                    <span class="text-lg font-bold {{ $filter === 'income' ? 'text-income' : 'text-expense' }} tabular-nums mt-0.5 block">
                        R$ {{ number_format($totalFilteredCents / 100, 2, ',', '.') }}
                    </span>
                </div>
                <div class="w-9 h-9 rounded-lg {{ $filter === 'income' ? 'bg-primary/10 text-primary' : 'bg-error/10 text-error' }} flex items-center justify-center">
                    <span class="material-symbols-outlined text-xl">{{ $filter === 'income' ? 'trending_up' : 'trending_down' }}</span>
                </div>
            </div>
            <div class="bg-surface-container-low rounded-xl p-4 ghost-border flex items-center justify-between hover-lift">
                <div>
                    <span class="text-[0.6875rem] uppercase tracking-wider text-on-surface-variant block">Média por Dia</span>
                    <span class="text-lg font-bold {{ $filter === 'income' ? 'text-income' : 'text-expense' }} tabular-nums mt-0.5 block">
                        R$ {{ number_format($dailyAverageCents / 100, 2, ',', '.') }}
                    </span>
                </div>
                <div class="w-9 h-9 rounded-lg bg-primary/10 flex items-center justify-center text-primary">
                    <span class="material-symbols-outlined text-xl">analytics</span>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Interactive Calendar Container -->
        <section class="lg:col-span-7 bg-surface-container-low rounded-2xl p-4 sm:p-5 ghost-border shadow-[0_12px_32px_rgba(0,0,0,0.35)] animate-stagger-2">
            <!-- Days of Week Header -->
            <div class="grid grid-cols-7 gap-1.5 mb-3 text-center">
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant/70">Seg</span>
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant/70">Ter</span>
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant/70">Qua</span>
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant/70">Qui</span>
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant/70">Sex</span>
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant/70">Sáb</span>
                <span class="text-[0.6875rem] font-bold uppercase tracking-widest text-on-surface-variant/70">Dom</span>
            </div>

            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-1.5">
                <!-- Previous Month Trailing Days -->
                @foreach($leadingDays as $prevDay)
                    <div class="aspect-square rounded-xl p-1.5 flex flex-col items-center justify-between opacity-25 select-none">
                        <span class="text-xs font-medium text-on-surface-variant">{{ $prevDay }}</span>
                    </div>
                @endforeach

                <!-- Current Month Days -->
                @foreach($byDay as $dayNum => $dayInfo)
                    @php
                        $isSelected = $selectedDay === $dayNum;
                        $amountReais = $dayInfo['total_cents'] / 100;
                        $hasActivity = $dayInfo['count'] > 0;

                        // Thermal intensity dot & label color:
                        // Para receitas: verde (primary)
                        // Para despesas: escala de despesas vívida (coral/âmbar/vermelho), 100% visível
                        if ($filter === 'income') {
                            $dotClass = 'bg-income';
                            $textClass = 'text-income';
                        } elseif ($amountReais > 350) {
                            $dotClass = 'bg-error';
                            $textClass = 'text-expense';
                        } elseif ($amountReais >= 150) {
                            $dotClass = 'bg-amber-400';
                            $textClass = 'text-expense';
                        } else {
                            $dotClass = 'bg-rose-400';
                            $textClass = 'text-expense';
                        }

                        // Compact formatting for calendar cell
                        if ($amountReais >= 1000) {
                            $shortAmount = number_format($amountReais / 1000, 1, ',', '') . 'k';
                        } else {
                            $shortAmount = number_format($amountReais, 0, ',', '.');
                        }
                    @endphp

                    <button
                        type="button"
                        wire:click="selectDay({{ $dayNum }})"
                        class="aspect-square rounded-xl p-1.5 flex flex-col items-center justify-between transition-all tap-bounce relative
                            {{ $isSelected
                                ? 'bg-primary/15 border-2 border-primary shadow-[0_0_20px_rgba(78,222,163,0.2)] scale-[1.03] z-10'
                                : ($hasActivity ? 'bg-surface-container hover:bg-surface-container-high' : 'hover:bg-surface-container-high') }}"
                    >
                        @if($isSelected)
                            <span class="text-xs font-extrabold text-primary">{{ $dayNum }}</span>
                        @elseif($hasActivity && $amountReais > 350 && $filter !== 'income')
                            <span class="text-xs font-bold text-on-surface">{{ $dayNum }}</span>
                        @elseif($hasActivity)
                            <span class="text-xs font-semibold text-on-surface">{{ $dayNum }}</span>
                        @else
                            <span class="text-xs font-semibold text-on-surface-variant">{{ $dayNum }}</span>
                        @endif

                        @if($hasActivity)
                            <div class="flex flex-col items-center gap-0.5">
                                <span class="w-2 h-2 rounded-full {{ $dotClass }} {{ $isSelected ? 'animate-pulse ring-2 ring-primary/40' : '' }} shrink-0 shadow-sm"></span>
                                <span class="text-[0.625rem] leading-tight {{ $isSelected ? 'font-extrabold text-on-surface' : 'font-bold ' . $textClass }} tabular-nums">
                                    {{ $shortAmount }}
                                </span>
                            </div>
                        @else
                            <span class="text-[0.6rem] text-outline/40 font-medium">-</span>
                        @endif
                    </button>
                @endforeach
            </div>

            <!-- Legend -->
            <div class="mt-4 pt-3 border-t border-outline-variant/10 flex items-center justify-between text-[0.6875rem] text-on-surface-variant">
                <div class="flex items-center gap-3 flex-wrap">
                    @if ($filter === 'income')
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-primary shadow-sm"></span> Receitas do dia
                        </span>
                    @else
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-rose-400 shadow-sm"></span> Baixo (&le; R$ 150)
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-amber-400 shadow-sm"></span> Moderado
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-error shadow-sm"></span> Intenso (&gt; R$ 350)
                        </span>
                    @endif
                </div>
                <span class="text-primary font-semibold hidden sm:inline">Toque no dia para explorar</span>
            </div>
        </section>

        <!-- Selected Day Detailed Feed -->
        <section class="lg:col-span-5 space-y-3.5 animate-stagger-3" wire:key="day-detail-{{ $month }}-{{ $selectedDay }}-{{ $filter }}">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="w-2 h-6 rounded-full bg-primary"></div>
                    <div>
                        <h3 class="text-sm font-bold text-on-surface tracking-tight">{{ $selectedDateLabel }}</h3>
                        <p class="text-[0.6875rem] text-on-surface-variant">
                            {{ count($selectedDayData['items']) }} {{ count($selectedDayData['items']) === 1 ? 'transação registrada' : 'transações registradas' }}
                        </p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="text-[0.65rem] uppercase tracking-wider text-on-surface-variant block">Total do Dia</span>
                    <span class="text-base font-extrabold {{ $filter === 'income' ? 'text-income' : 'text-expense' }} tabular-nums">
                        {{ $filter === 'income' ? '+' : '-' }} R$ {{ number_format($selectedDayData['total_cents'] / 100, 2, ',', '.') }}
                    </span>
                </div>
            </div>

            <!-- Transactions Stack -->
            <div class="space-y-2.5">
                @forelse($selectedDayData['items'] as $tx)
                    @php
                        $isIncome = $tx->amount_cents >= 0;
                        $catName = $tx->category->name ?? ($isIncome ? 'Receita' : 'Sem categoria');
                        $iconName = $tx->category
                            ? \App\Support\MaterialIcon::forCategory($tx->category->name, $tx->category->kind)
                            : ($isIncome ? 'trending_up' : 'receipt_long');
                        $badgeClasses = \App\Support\MaterialIcon::badgeClasses($catName, $isIncome ? 'income' : 'expense');

                        $parsedBrand = \App\Support\BankBrand::parse($tx);
                        $bankBrand = $parsedBrand['brand'];
                        $cleanTitle = $parsedBrand['cleanDescription'];
                    @endphp
                    <div class="bg-surface-container-low hover:bg-surface-container transition-all rounded-xl p-3.5 ghost-border flex items-center justify-between group hover-lift animate-pop">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div class="w-11 h-11 rounded-xl {{ $badgeClasses }} flex items-center justify-center shrink-0">
                                <span class="material-symbols-outlined">{{ $iconName }}</span>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5 truncate">
                                    @if ($bankBrand)
                                        <x-bank-icon :brand="$bankBrand" size="md" />
                                    @endif
                                    <h4 class="text-sm font-bold text-on-surface group-hover:text-primary transition-colors truncate">{{ $cleanTitle }}</h4>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                    <span class="text-[0.6875rem] font-medium px-2 py-0.5 rounded bg-surface-container-highest text-on-surface-variant flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[13px]">{{ $iconName }}</span>
                                        <span>{{ $catName }}</span>
                                    </span>
                                    <span class="text-[0.6875rem] text-on-surface-variant flex items-center gap-1">
                                        <span class="material-symbols-outlined text-[12px]">credit_card</span>
                                        {{ $tx->account->name ?? 'Conta' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="text-right shrink-0 ml-3">
                            <span class="text-sm font-bold {{ $tx->type === 'income' ? 'text-income' : 'text-expense' }} tabular-nums block">
                                {{ $tx->type === 'income' ? '+' : '-' }} R$ {{ number_format(abs($tx->amount_cents) / 100, 2, ',', '.') }}
                            </span>
                            <a href="{{ route('transactions.create', ['editar' => $tx->id]) }}" class="text-[0.65rem] text-primary hover:underline font-semibold">
                                Editar
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="bg-surface-container-low rounded-xl p-8 ghost-border text-center space-y-3">
                        <div class="w-12 h-12 rounded-2xl bg-surface-container-high text-on-surface-variant flex items-center justify-center mx-auto">
                            <span class="material-symbols-outlined text-2xl">event_available</span>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-on-surface">Nenhuma movimentação neste dia</p>
                            <p class="text-xs text-on-surface-variant mt-0.5">Que tal registrar um novo lançamento para {{ $selectedDayData['date']->format('d/m/Y') }}?</p>
                        </div>
                        <a href="{{ route('transactions.create', ['data' => $selectedDayData['date']->toDateString()]) }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-primary/15 text-primary text-xs font-bold hover:bg-primary/25 transition-colors tap-bounce">
                            <span class="material-symbols-outlined text-base">add</span>
                            Lançar neste dia
                        </a>
                    </div>
                @endforelse
            </div>

            <!-- Daily Insight Banner -->
            <div class="bg-gradient-to-r from-surface-container-low via-surface-container to-surface-container-low rounded-xl p-3.5 ghost-border flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-primary/15 text-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-lg">lightbulb</span>
                </div>
                <p class="text-xs text-on-surface-variant leading-relaxed">
                    @if($selectedDayData['total_cents'] > 0 && $dailyAverageCents > 0)
                        Neste dia o volume foi
                        <span class="text-on-surface font-semibold">
                            {{ abs($diffVsAvgPercent) }}% {{ $diffVsAvgPercent >= 0 ? 'acima' : 'abaixo' }}
                        </span>
                        da sua média diária do mês{{ $topCategoryOnDay ? ', impulsionado por ' : '.' }}
                        @if($topCategoryOnDay)
                            <span class="text-primary font-medium">{{ $topCategoryOnDay }}</span>.
                        @endif
                    @else
                        Dia sem gastos registrados — excelente para manter sua média diária equilibrada em <span class="{{ $filter === 'income' ? 'text-income' : 'text-expense' }} font-semibold">R$ {{ number_format($dailyAverageCents / 100, 2, ',', '.') }}</span>.
                    @endif
                </p>
            </div>
        </section>
    </div>
</div>

