<div class="space-y-6 animate-page-enter">
    <!-- Top Section: Segmented View Switcher & Period Selector -->
    <section class="pt-1 space-y-4 animate-stagger-1">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Segmented Tabs -->
            <div class="bg-surface-container-low p-1 rounded-xl ghost-border grid grid-cols-3 gap-1 sm:w-auto">
                <button type="button" wire:click="$set('activeTab', 'all')"
                    class="py-2 px-3 rounded-lg text-xs font-bold transition-all tap-bounce {{ $activeTab === 'all' ? 'bg-surface-container-high text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}">
                    Visão Geral
                </button>
                <button type="button" wire:click="$set('activeTab', 'comparativo')"
                    class="py-2 px-3 rounded-lg text-xs font-bold transition-all tap-bounce {{ $activeTab === 'comparativo' ? 'bg-surface-container-high text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}">
                    Comparativo Mensal
                </button>
                <button type="button" wire:click="$set('activeTab', 'categorias')"
                    class="py-2 px-3 rounded-lg text-xs font-bold transition-all tap-bounce {{ $activeTab === 'categorias' ? 'bg-surface-container-high text-primary shadow-sm' : 'text-on-surface-variant hover:text-on-surface' }}">
                    Por Categoria
                </button>
            </div>

            <!-- Month Navigation -->
            <div class="flex items-center justify-between sm:justify-end gap-2 bg-surface-container-low px-3 py-1.5 rounded-xl ghost-border">
                <button type="button" wire:click="previousMonth" class="w-7 h-7 rounded-lg hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors tap-bounce">
                    <span class="material-symbols-outlined text-base">chevron_left</span>
                </button>
                <span class="text-xs font-bold text-on-surface min-w-[90px] text-center">{{ $currentMonthLabel }} vs {{ $prevMonthLabel }}</span>
                <button type="button" wire:click="nextMonth" class="w-7 h-7 rounded-lg hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant hover:text-primary transition-colors tap-bounce">
                    <span class="material-symbols-outlined text-base">chevron_right</span>
                </button>
            </div>
        </div>

        @if($savedNotice)
            <div class="bg-primary/15 border border-primary/30 rounded-xl p-3 flex items-center gap-2.5 text-xs font-semibold text-primary animate-pop">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <span>{{ $savedNotice }}</span>
            </div>
        @endif

        <!-- Card Salário / Renda de Referência do Orçamento -->
        <div class="rounded-2xl bg-surface-container-low p-4 sm:p-5 ghost-border space-y-4 shadow-md">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-primary/15 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined">payments</span>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-sm font-bold text-on-surface">Salário / Renda de Referência</h2>
                            @if($salaryCents > 0)
                                <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold text-primary">Base Ativa</span>
                            @else
                                <span class="rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-500">Usando Entradas Reais</span>
                            @endif
                        </div>
                        <p class="text-[0.6875rem] text-on-surface-variant">Referência total para cálculo das metas por categoria e regra 50/30/20.</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    @if(! $editingSalary)
                        <div class="flex items-baseline gap-1.5 mr-1">
                            <span class="text-xs text-on-surface-variant font-medium">Referência:</span>
                            <span class="text-base font-extrabold text-primary tabular-nums">
                                R$ {{ number_format($referenceSalaryCents / 100, 2, ',', '.') }}
                            </span>
                        </div>
                        <button type="button" wire:click="toggleEditSalary" class="rounded-xl bg-surface-container-high px-3 py-1.5 text-xs font-semibold text-on-surface hover:text-primary transition-colors flex items-center gap-1 tap-bounce">
                            <span class="material-symbols-outlined text-sm">edit</span>
                            <span>{{ $salaryCents > 0 ? 'Alterar Salário' : 'Inserir Salário' }}</span>
                        </button>
                    @endif
                </div>
            </div>

            @if($editingSalary)
                <form wire:submit="saveSalary" class="p-3.5 rounded-xl bg-surface-container border border-outline-variant/20 space-y-3 animate-pop">
                    <label class="block text-xs font-semibold text-on-surface">Informe seu salário ou renda mensal líquida (R$):</label>
                    <div class="flex flex-wrap items-center gap-2">
                        <div class="flex items-center gap-1.5 bg-surface-container-lowest px-3 py-2 rounded-lg border border-outline-variant/30 flex-1 max-w-xs">
                            <span class="text-sm font-bold text-primary">R$</span>
                            <input
                                type="text"
                                inputmode="decimal"
                                wire:model="salaryInput"
                                placeholder="Ex: 5.000,00"
                                autofocus
                                class="w-full bg-transparent border-none p-0 text-sm font-bold text-on-surface focus:outline-none tabular-nums"
                            />
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-lg bg-primary text-on-primary text-xs font-bold hover:bg-primary-fixed transition-colors tap-bounce">
                            Salvar Salário
                        </button>
                        <button type="button" wire:click="toggleEditSalary" class="px-3 py-2 rounded-lg bg-surface-container-high text-on-surface-variant text-xs font-semibold hover:text-on-surface">
                            Cancelar
                        </button>
                    </div>
                </form>
            @endif

            <!-- Visão de Comprometimento do Salário no Mês -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-3 border-t border-outline-variant/15">
                <div class="bg-surface-container/60 rounded-xl p-3">
                    <span class="text-[0.65rem] font-semibold uppercase tracking-wider text-on-surface-variant block">Salário de Referência</span>
                    <span class="text-base font-extrabold text-on-surface tabular-nums mt-0.5 block">
                        R$ {{ number_format($referenceSalaryCents / 100, 2, ',', '.') }}
                    </span>
                    <span class="text-[0.6rem] text-on-surface-variant block mt-0.5">
                        {{ $salaryCents > 0 ? 'Salário cadastrado' : 'Entradas do mês' }}
                    </span>
                </div>
                <div class="bg-surface-container/60 rounded-xl p-3">
                    <span class="text-[0.65rem] font-semibold uppercase tracking-wider text-on-surface-variant block">Gastos Orçados</span>
                    <span class="text-base font-extrabold text-expense tabular-nums mt-0.5 block">
                        R$ {{ number_format($currentExpenseCents / 100, 2, ',', '.') }}
                    </span>
                    <span class="text-[0.6rem] {{ $budgetUsagePct > 100 ? 'text-error font-bold' : 'text-on-surface-variant' }} block mt-0.5">
                        {{ number_format($budgetUsagePct, 1, ',', '.') }}% do total
                    </span>
                </div>
                <div class="bg-surface-container/60 rounded-xl p-3">
                    <span class="text-[0.65rem] font-semibold uppercase tracking-wider text-on-surface-variant block">Saldo Disponível</span>
                    <span class="text-base font-extrabold {{ $salarySurplusCents >= 0 ? 'text-primary' : 'text-error' }} tabular-nums mt-0.5 block">
                        R$ {{ number_format(abs($salarySurplusCents) / 100, 2, ',', '.') }}
                    </span>
                    <span class="text-[0.6rem] text-on-surface-variant block mt-0.5">
                        {{ $salarySurplusCents >= 0 ? 'Livre no mês' : 'Excedeu a referência' }}
                    </span>
                </div>
            </div>

            <!-- Barra de Progresso do Orçamento -->
            <div class="space-y-1.5 pt-1">
                <div class="flex items-center justify-between text-xs font-semibold">
                    <span class="text-on-surface-variant text-[0.6875rem]">Progresso do consumo da renda</span>
                    <span class="{{ $budgetUsagePct > 100 ? 'text-error' : 'text-primary' }} tabular-nums">{{ number_format(min(100, $budgetUsagePct), 1, ',', '.') }}% utilizado</span>
                </div>
                <div class="h-2 w-full rounded-full bg-surface-container-highest overflow-hidden p-0.5">
                    <div
                        class="h-full rounded-full transition-all duration-500 {{ $budgetUsagePct > 100 ? 'bg-error' : ($budgetUsagePct >= 80 ? 'bg-amber-400' : 'bg-primary') }}"
                        style="width: {{ min(100, max(2, $budgetUsagePct)) }}%"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Hero Month-over-Month Performance Card -->
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-surface-container-high via-surface-container to-surface-container-low p-6 ghost-border shadow-[0_20px_40px_rgba(0,0,0,0.4)]">
            <div class="absolute -top-20 -right-20 w-56 h-56 rounded-full bg-primary/10 blur-3xl pointer-events-none"></div>
            <div class="relative z-10 flex flex-col gap-5">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant block">
                            Eficiência Mensal ({{ $currentMonthLabel }} vs {{ $prevMonthLabel }})
                        </span>
                        <div class="flex items-baseline gap-2.5 mt-1 flex-wrap">
                            <h2 class="text-3xl font-extrabold tracking-tight {{ $efficiencyDiffPercent <= 0 ? 'text-primary' : 'text-error' }} tabular-nums">
                                {{ $efficiencyDiffPercent > 0 ? '+' : '' }}{{ number_format($efficiencyDiffPercent, 1, ',', '.') }}% em gastos
                            </h2>
                            <span class="text-xs font-semibold {{ $savingsDiffCents >= 0 ? 'text-income bg-primary-container/25 border-primary/20' : 'text-expense bg-error/15 border-error/20' }} px-2 py-0.5 rounded-full border">
                                {{ $savingsDiffCents >= 0 ? 'Economia de' : 'Aumento de' }} R$ {{ number_format(abs($savingsDiffCents) / 100, 2, ',', '.') }}
                            </span>
                        </div>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-primary/15 text-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined">{{ $efficiencyDiffPercent <= 0 ? 'trending_down' : 'trending_up' }}</span>
                    </div>
                </div>

                <!-- Dual Comparison Metrics -->
                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-outline-variant/15">
                    <div>
                        <span class="text-[0.65rem] font-semibold uppercase tracking-wider text-on-surface-variant block">Saídas {{ $currentMonthLabel }}</span>
                        <span class="text-lg font-bold text-expense tabular-nums mt-0.5 block">
                            R$ {{ number_format($currentExpenseCents / 100, 2, ',', '.') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[0.65rem] font-semibold uppercase tracking-wider text-on-surface-variant block">Saídas {{ $prevMonthLabel }}</span>
                        <span class="text-lg font-bold text-expense tabular-nums mt-0.5 block">
                            R$ {{ number_format($prevExpenseCents / 100, 2, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left Column: Comparative Bar Chart & 50/30/20 Rule -->
        @if($activeTab === 'all' || $activeTab === 'comparativo')
            <div class="{{ $activeTab === 'comparativo' ? 'lg:col-span-12' : 'lg:col-span-6' }} space-y-6 animate-stagger-2">
                <!-- Comparative Bar Chart -->
                <section class="bg-surface-container-low rounded-2xl p-5 ghost-border space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-on-surface tracking-tight">Evolução Comparativa (4 Meses)</h3>
                            <p class="text-[0.6875rem] text-on-surface-variant">Comparativo entre Receitas e Despesas</p>
                        </div>
                        <div class="flex items-center gap-3 text-[0.6875rem] font-semibold">
                            <span class="flex items-center gap-1.5 text-on-surface-variant">
                                <span class="w-2.5 h-2.5 rounded-sm bg-primary"></span> Entradas
                            </span>
                            <span class="flex items-center gap-1.5 text-on-surface-variant">
                                <span class="w-2.5 h-2.5 rounded-sm bg-error"></span> Saídas
                            </span>
                        </div>
                    </div>

                    <!-- Custom CSS Bar Chart -->
                    <div class="pt-4 pb-1">
                        <div class="grid grid-cols-4 gap-4 items-end h-44 px-2 border-b border-outline-variant/15">
                            @foreach($monthsComparison as $mc)
                                <div class="flex flex-col items-center gap-2 h-full justify-end relative">
                                    @if($mc['is_current'])
                                        <div class="absolute -top-6 bg-surface-container-highest px-2 py-0.5 rounded text-[0.6rem] font-bold text-primary border border-primary/20 whitespace-nowrap">
                                            Atual
                                        </div>
                                    @endif
                                    <div class="w-full flex items-end justify-center gap-1.5 h-full">
                                        <div
                                            class="w-3.5 {{ $mc['is_current'] ? 'bg-income shadow-[0_0_12px_rgba(74,222,128,0.3)]' : 'bg-income/45 hover:bg-income/70' }} rounded-t-md transition-all animate-bar-y"
                                            style="height: {{ $mc['income_height_pct'] }}%"
                                            title="Entradas: R$ {{ number_format($mc['income_cents'] / 100, 2, ',', '.') }}"
                                        ></div>
                                        <div
                                            class="w-3.5 {{ $mc['is_current'] ? 'bg-expense/90 shadow-[0_0_12px_rgba(248,113,113,0.2)]' : 'bg-expense/45 hover:bg-expense/70' }} rounded-t-md transition-all animate-bar-y"
                                            style="height: {{ $mc['expense_height_pct'] }}%"
                                            title="Saídas: R$ {{ number_format($mc['expense_cents'] / 100, 2, ',', '.') }}"
                                        ></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="grid grid-cols-4 gap-4 text-center mt-2.5 text-[0.6875rem] font-semibold text-on-surface-variant">
                            @foreach($monthsComparison as $mc)
                                <span class="{{ $mc['is_current'] ? 'text-primary font-bold' : '' }}">
                                    {{ $mc['label'] }}{{ $mc['is_current'] ? ' (Atual)' : '' }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    <!-- Analytical Insight Box -->
                    <div class="bg-surface-container rounded-xl p-3.5 flex items-start gap-3 ghost-border">
                        <span class="material-symbols-outlined text-primary text-lg mt-0.5">auto_awesome</span>
                        <p class="text-xs text-on-surface-variant leading-relaxed">
                            Em <strong class="text-on-surface">{{ $currentMonthLabel }}</strong>, suas saídas somam
                            <strong class="text-expense">R$ {{ number_format($currentExpenseCents / 100, 2, ',', '.') }}</strong>.
                            @if($categoryLimits->first())
                                A categoria com maior volume no período é
                                <span class="text-primary font-semibold">{{ $categoryLimits->first()['name'] }}</span>
                                (<span class="text-expense">R$ {{ number_format($categoryLimits->first()['spent_cents'] / 100, 2, ',', '.') }}</span>).
                            @endif
                        </p>
                    </div>
                </section>

                <!-- 50 / 30 / 20 Strategic Allocation Bar -->
                <section class="bg-surface-container-low rounded-2xl p-5 ghost-border space-y-4" x-data="{ showDetails: true }">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-on-surface">Regra Estratégica 50 / 30 / 20</h3>
                            <p class="text-[0.6875rem] text-on-surface-variant">Equilíbrio entre Essenciais (50%), Estilo de Vida (30%) e Investimentos (20%)</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if($essentialExceeded && $lifestyleExceeded)
                                <span class="text-xs font-bold text-expense bg-expense/10 px-2.5 py-1 rounded-lg">Orçamento Pressionado</span>
                            @elseif($essentialExceeded)
                                <span class="text-xs font-bold text-amber-500 bg-amber-500/10 px-2.5 py-1 rounded-lg">Essenciais Acima da Meta</span>
                            @elseif($lifestyleExceeded)
                                <span class="text-xs font-bold text-amber-500 bg-amber-500/10 px-2.5 py-1 rounded-lg">Estilo Acima da Meta</span>
                            @else
                                <span class="text-xs font-bold text-income bg-income/10 px-2.5 py-1 rounded-lg">Distribuição Saudável</span>
                            @endif

                            <button
                                type="button"
                                @click="showDetails = !showDetails"
                                class="text-xs font-semibold text-primary hover:text-primary-fixed flex items-center gap-1 bg-primary/10 hover:bg-primary/20 px-2.5 py-1 rounded-lg transition-all tap-bounce"
                                title="Alternar visão detalhada de categorias"
                            >
                                <span class="material-symbols-outlined text-sm" x-text="showDetails ? 'expand_less' : 'tune'">tune</span>
                                <span x-text="showDetails ? 'Recolher' : 'Expandir'">Recolher</span>
                            </button>
                        </div>
                    </div>

                    <!-- Multi-Segment Progress Bar -->
                    <div class="h-3 w-full rounded-full bg-surface-container-highest overflow-hidden flex gap-0.5 p-0.5">
                        <div class="h-full bg-primary rounded-l-full animate-bar-x" style="width: {{ max(5, $essentialPct) }}%" title="Essenciais ({{ $essentialPct }}%)"></div>
                        <div class="h-full bg-tertiary animate-bar-x" style="width: {{ max(5, $lifestylePct) }}%" title="Estilo de Vida ({{ $lifestylePct }}%)"></div>
                        <div class="h-full bg-secondary-fixed-dim rounded-r-full animate-bar-x" style="width: {{ max(5, $investPct) }}%" title="Investimentos ({{ $investPct }}%)"></div>
                    </div>

                    <!-- Mini KPI Overview Cards -->
                    <div class="grid grid-cols-3 gap-2 pt-1 text-center">
                        <div class="bg-surface-container rounded-xl p-2.5 {{ $essentialExceeded ? 'border border-expense/20' : '' }}">
                            <span class="text-[0.625rem] uppercase tracking-wider text-on-surface-variant block truncate">Essenciais</span>
                            <span class="text-sm font-extrabold {{ $essentialExceeded ? 'text-expense' : 'text-primary' }} tabular-nums">{{ $essentialPct }}%</span>
                            <span class="text-[0.6rem] text-on-surface-variant block mt-0.5">Meta: 50%</span>
                            <span class="text-[0.65rem] {{ $essentialExceeded ? 'text-expense font-bold' : 'text-primary/80 font-semibold' }} block mt-0.5">
                                R$ {{ number_format($essentialCents / 100, 0, ',', '.') }} / R$ {{ number_format($essentialTargetCents / 100, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="bg-surface-container rounded-xl p-2.5 {{ $lifestyleExceeded ? 'border border-expense/20' : '' }}">
                            <span class="text-[0.625rem] uppercase tracking-wider text-on-surface-variant block truncate">Estilo de Vida</span>
                            <span class="text-sm font-extrabold {{ $lifestyleExceeded ? 'text-expense' : 'text-tertiary' }} tabular-nums">{{ $lifestylePct }}%</span>
                            <span class="text-[0.6rem] text-on-surface-variant block mt-0.5">Meta: 30%</span>
                            <span class="text-[0.65rem] {{ $lifestyleExceeded ? 'text-expense font-bold' : 'text-tertiary/80 font-semibold' }} block mt-0.5">
                                R$ {{ number_format($lifestyleCents / 100, 0, ',', '.') }} / R$ {{ number_format($lifestyleTargetCents / 100, 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="bg-surface-container rounded-xl p-2.5">
                            <span class="text-[0.625rem] uppercase tracking-wider text-on-surface-variant block truncate">Investimentos</span>
                            <span class="text-sm font-extrabold text-secondary-fixed-dim tabular-nums">{{ $investPct }}%</span>
                            <span class="text-[0.6rem] text-on-surface-variant block mt-0.5">Meta: 20%</span>
                            <span class="text-[0.65rem] text-secondary-fixed-dim/80 font-bold block mt-0.5">
                                R$ {{ number_format($investAvailableCents / 100, 0, ',', '.') }} / R$ {{ number_format($investTargetCents / 100, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <!-- Detalhamento Completo das Categorias e Totalidades no 50/30/20 -->
                    <div x-show="showDetails" x-cloak x-collapse class="space-y-4 pt-1 animate-fade-in">
                        <div class="flex items-center justify-between pb-1">
                            <span class="text-xs font-bold text-on-surface">Detalhamento por Grupo & Status de Meta</span>
                            <a href="{{ route('categories') }}" class="text-[0.6875rem] font-semibold text-primary hover:underline flex items-center gap-1">
                                <span class="material-symbols-outlined text-xs">edit_note</span>
                                Configurar Categorias
                            </a>
                        </div>

                        <!-- Pilar 1: Essenciais (50%) -->
                        <div class="bg-surface-container rounded-2xl p-4 ghost-border space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2.5">
                                    <span class="w-3 h-3 rounded-full bg-primary inline-block shrink-0 mt-1"></span>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-sm font-bold text-on-surface">Essenciais (50%)</h4>
                                            @if($essentialExceeded)
                                                <span class="text-[0.625rem] font-bold text-expense bg-expense/10 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">warning</span>
                                                    Ultrapassou a meta em R$ {{ number_format($essentialDiffCents / 100, 2, ',', '.') }} ({{ $essentialUsagePct }}% do teto)
                                                </span>
                                            @else
                                                <span class="text-[0.625rem] font-bold text-income bg-income/10 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">check_circle</span>
                                                    Dentro da meta (restam R$ {{ number_format($essentialDiffCents / 100, 2, ',', '.') }})
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[0.6875rem] text-on-surface-variant">Necessidades básicas: moradia, transporte, saúde, alimentação e educação</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-sm font-extrabold text-on-surface tabular-nums">
                                        <span class="{{ $essentialExceeded ? 'text-expense font-black' : 'text-primary' }}">R$ {{ number_format($essentialCents / 100, 2, ',', '.') }}</span>
                                        <span class="text-xs font-normal text-on-surface-variant"> / R$ {{ number_format($essentialTargetCents / 100, 2, ',', '.') }}</span>
                                    </div>
                                    <span class="text-[0.625rem] font-bold {{ $essentialExceeded ? 'text-expense' : 'text-primary' }} block">
                                        Totalidade: {{ $essentialPct }}% do salário
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar do Pilar -->
                            <div class="h-2 w-full rounded-full bg-surface-container-highest overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $essentialExceeded ? 'bg-expense' : 'bg-primary' }}" style="width: {{ min(100, $essentialUsagePct) }}%"></div>
                            </div>

                            <!-- Categorias do Grupo -->
                            <div class="space-y-1.5 pt-1" x-data="{ expandedCat: null }">
                                <span class="text-[0.6875rem] font-semibold text-on-surface-variant uppercase tracking-wider block">Categorias consideradas nos 50%:</span>
                                <div class="space-y-1.5">
                                    @forelse($essentialCategories as $item)
                                        @php
                                            $txList = $item['transactions'] ?? [];
                                        @endphp
                                        <div class="bg-surface-container-high/60 rounded-xl overflow-hidden transition-all border border-outline-variant/10">
                                            <div
                                                @click="expandedCat = (expandedCat === '{{ $item['id'] }}' ? null : '{{ $item['id'] }}')"
                                                class="flex items-center justify-between p-3 text-xs cursor-pointer select-none hover:bg-surface-container-high transition-colors group"
                                            >
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $item['color'] }}"></span>
                                                    <span class="font-bold text-on-surface group-hover:text-primary transition-colors truncate text-sm">{{ $item['name'] }}</span>
                                                    @if($item['tx_count'] > 0)
                                                        <span class="text-[10px] font-semibold text-on-surface-variant bg-surface-container-highest px-2 py-0.5 rounded-full shrink-0">
                                                            {{ $item['tx_count'] }} {{ $item['tx_count'] === 1 ? 'item' : 'itens' }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2.5 shrink-0 ml-2">
                                                    <span class="text-sm font-extrabold text-on-surface tabular-nums">
                                                        R$ {{ $item['spent_formatted'] }}
                                                    </span>
                                                    <div class="w-6 h-6 rounded-lg bg-surface-container-highest flex items-center justify-center text-on-surface-variant group-hover:text-primary group-hover:bg-primary/10 transition-colors">
                                                        <span
                                                            class="material-symbols-outlined text-[16px] transition-transform duration-200"
                                                            :class="expandedCat === '{{ $item['id'] }}' ? 'rotate-180 text-primary' : ''"
                                                        >expand_more</span>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Gaveta Expansível de Transações da Categoria --}}
                                            <div
                                                x-show="expandedCat === '{{ $item['id'] }}'"
                                                x-cloak
                                                x-collapse
                                                class="px-3 pb-3 pt-1 border-t border-outline-variant/10 space-y-2 bg-surface-container-lowest/40"
                                            >
                                                @if(count($txList) === 0)
                                                    <p class="text-[0.6875rem] text-on-surface-variant py-2 text-center italic">Nenhum lançamento registrado no mês.</p>
                                                @else
                                                    <div class="space-y-1 max-h-60 overflow-y-auto pr-1 divide-y divide-outline-variant/10">
                                                        @foreach($txList as $tx)
                                                            <a
                                                                href="{{ route('transactions.create', ['editar' => $tx['id']]) }}"
                                                                wire:navigate
                                                                class="pt-1.5 first:pt-0 flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-surface-container-highest/60 transition-colors group/tx"
                                                            >
                                                                <div class="flex items-center gap-2 min-w-0">
                                                                    <span class="text-[11px] font-semibold text-on-surface-variant/70 tabular-nums shrink-0">
                                                                        {{ $tx['date_formatted'] }}
                                                                    </span>
                                                                    @if($tx['bank_brand'])
                                                                        <x-bank-icon :brand="$tx['bank_brand']" size="xs" />
                                                                    @endif
                                                                    <span class="text-xs text-on-surface truncate group-hover/tx:text-primary transition-colors">
                                                                        {{ $tx['clean_title'] ?: $tx['description'] }}
                                                                    </span>
                                                                </div>
                                                                <span class="text-xs font-bold shrink-0 tabular-nums {{ $tx['is_income'] ? 'text-income' : 'text-expense' }}">
                                                                    {{ $tx['is_income'] ? '+' : '-' }} R$ {{ $tx['amount_formatted'] }}
                                                                </span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-[0.6875rem] text-on-surface-variant italic py-2 text-center">Nenhum gasto essencial registrado este mês.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- Pilar 2: Estilo de Vida (30%) -->
                        <div class="bg-surface-container rounded-2xl p-4 ghost-border space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2.5">
                                    <span class="w-3 h-3 rounded-full bg-tertiary inline-block shrink-0 mt-1"></span>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-sm font-bold text-on-surface">Estilo de Vida (30%)</h4>
                                            @if($lifestyleExceeded)
                                                <span class="text-[0.625rem] font-bold text-expense bg-expense/10 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">warning</span>
                                                    Ultrapassou a meta em R$ {{ number_format($lifestyleDiffCents / 100, 2, ',', '.') }} ({{ $lifestyleUsagePct }}% do teto)
                                                </span>
                                            @else
                                                <span class="text-[0.625rem] font-bold text-income bg-income/10 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">check_circle</span>
                                                    Dentro da meta (restam R$ {{ number_format($lifestyleDiffCents / 100, 2, ',', '.') }})
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[0.6875rem] text-on-surface-variant">Lazer, compras pessoais, passeios, pets e assinaturas</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-sm font-extrabold text-on-surface tabular-nums">
                                        <span class="{{ $lifestyleExceeded ? 'text-expense font-black' : 'text-tertiary' }}">R$ {{ number_format($lifestyleCents / 100, 2, ',', '.') }}</span>
                                        <span class="text-xs font-normal text-on-surface-variant"> / R$ {{ number_format($lifestyleTargetCents / 100, 2, ',', '.') }}</span>
                                    </div>
                                    <span class="text-[0.625rem] font-bold {{ $lifestyleExceeded ? 'text-expense' : 'text-tertiary' }} block">
                                        Totalidade: {{ $lifestylePct }}% do salário
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar do Pilar -->
                            <div class="h-2 w-full rounded-full bg-surface-container-highest overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ $lifestyleExceeded ? 'bg-expense' : 'bg-tertiary' }}" style="width: {{ min(100, $lifestyleUsagePct) }}%"></div>
                            </div>

                            <!-- Categorias do Grupo -->
                            <div class="space-y-1.5 pt-1" x-data="{ expandedCat: null }">
                                <span class="text-[0.6875rem] font-semibold text-on-surface-variant uppercase tracking-wider block">Categorias consideradas nos 30%:</span>
                                <div class="space-y-1.5">
                                    @forelse($lifestyleCategories as $item)
                                        @php
                                            $txList = $item['transactions'] ?? [];
                                        @endphp
                                        <div class="bg-surface-container-high/60 rounded-xl overflow-hidden transition-all border border-outline-variant/10">
                                            <div
                                                @click="expandedCat = (expandedCat === '{{ $item['id'] }}' ? null : '{{ $item['id'] }}')"
                                                class="flex items-center justify-between p-3 text-xs cursor-pointer select-none hover:bg-surface-container-high transition-colors group"
                                            >
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $item['color'] }}"></span>
                                                    <span class="font-bold text-on-surface group-hover:text-primary transition-colors truncate text-sm">{{ $item['name'] }}</span>
                                                    @if($item['tx_count'] > 0)
                                                        <span class="text-[10px] font-semibold text-on-surface-variant bg-surface-container-highest px-2 py-0.5 rounded-full shrink-0">
                                                            {{ $item['tx_count'] }} {{ $item['tx_count'] === 1 ? 'item' : 'itens' }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="flex items-center gap-2.5 shrink-0 ml-2">
                                                    <span class="text-sm font-extrabold text-on-surface tabular-nums">
                                                        R$ {{ $item['spent_formatted'] }}
                                                    </span>
                                                    <div class="w-6 h-6 rounded-lg bg-surface-container-highest flex items-center justify-center text-on-surface-variant group-hover:text-primary group-hover:bg-primary/10 transition-colors">
                                                        <span
                                                            class="material-symbols-outlined text-[16px] transition-transform duration-200"
                                                            :class="expandedCat === '{{ $item['id'] }}' ? 'rotate-180 text-primary' : ''"
                                                        >expand_more</span>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Gaveta Expansível de Transações da Categoria --}}
                                            <div
                                                x-show="expandedCat === '{{ $item['id'] }}'"
                                                x-cloak
                                                x-collapse
                                                class="px-3 pb-3 pt-1 border-t border-outline-variant/10 space-y-2 bg-surface-container-lowest/40"
                                            >
                                                @if(count($txList) === 0)
                                                    <p class="text-[0.6875rem] text-on-surface-variant py-2 text-center italic">Nenhum lançamento registrado no mês.</p>
                                                @else
                                                    <div class="space-y-1 max-h-60 overflow-y-auto pr-1 divide-y divide-outline-variant/10">
                                                        @foreach($txList as $tx)
                                                            <a
                                                                href="{{ route('transactions.create', ['editar' => $tx['id']]) }}"
                                                                wire:navigate
                                                                class="pt-1.5 first:pt-0 flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-surface-container-highest/60 transition-colors group/tx"
                                                            >
                                                                <div class="flex items-center gap-2 min-w-0">
                                                                    <span class="text-[11px] font-semibold text-on-surface-variant/70 tabular-nums shrink-0">
                                                                        {{ $tx['date_formatted'] }}
                                                                    </span>
                                                                    @if($tx['bank_brand'])
                                                                        <x-bank-icon :brand="$tx['bank_brand']" size="xs" />
                                                                    @endif
                                                                    <span class="text-xs text-on-surface truncate group-hover/tx:text-primary transition-colors">
                                                                        {{ $tx['clean_title'] ?: $tx['description'] }}
                                                                    </span>
                                                                </div>
                                                                <span class="text-xs font-bold shrink-0 tabular-nums {{ $tx['is_income'] ? 'text-income' : 'text-expense' }}">
                                                                    {{ $tx['is_income'] ? '+' : '-' }} R$ {{ $tx['amount_formatted'] }}
                                                                </span>
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="text-[0.6875rem] text-on-surface-variant italic py-2 text-center">Nenhum gasto de estilo de vida registrado este mês.</p>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <!-- Pilar 3: Investimentos & Futuro (20%) -->
                        <div class="bg-surface-container rounded-2xl p-4 ghost-border space-y-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-start gap-2.5">
                                    <span class="w-3 h-3 rounded-full bg-secondary-fixed-dim inline-block shrink-0 mt-1"></span>
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h4 class="text-sm font-bold text-on-surface">Investimentos & Reserva (20%)</h4>
                                            @if($investTargetMet)
                                                <span class="text-[0.625rem] font-bold text-income bg-income/10 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">check_circle</span>
                                                    Meta atingida (+R$ {{ number_format($investDiffCents / 100, 2, ',', '.') }} de margem)
                                                </span>
                                            @else
                                                <span class="text-[0.625rem] font-bold text-amber-500 bg-amber-500/10 px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                                    <span class="material-symbols-outlined text-xs">schedule</span>
                                                    Abaixo da meta recomendada (faltam R$ {{ number_format($investDiffCents / 100, 2, ',', '.') }})
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-[0.6875rem] text-on-surface-variant">Sobra livre e aportes para reserva de emergência e investimentos</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <div class="text-sm font-extrabold text-on-surface tabular-nums">
                                        <span class="{{ $investTargetMet ? 'text-secondary-fixed-dim' : 'text-amber-500' }}">R$ {{ number_format($investAvailableCents / 100, 2, ',', '.') }}</span>
                                        <span class="text-xs font-normal text-on-surface-variant"> / R$ {{ number_format($investTargetCents / 100, 2, ',', '.') }}</span>
                                    </div>
                                    <span class="text-[0.625rem] font-bold {{ $investTargetMet ? 'text-secondary-fixed-dim' : 'text-amber-500' }} block">
                                        Totalidade: {{ $investPct }}% do salário
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar do Pilar -->
                            <div class="h-2 w-full rounded-full bg-surface-container-highest overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 bg-secondary-fixed-dim" style="width: {{ min(100, $investUsagePct) }}%"></div>
                            </div>

                            @if(count($investCategories) > 0)
                                <div class="space-y-1.5 pt-1" x-data="{ expandedCat: null }">
                                    <span class="text-[0.6875rem] font-semibold text-on-surface-variant uppercase tracking-wider block">Categorias de Investimento direto:</span>
                                    <div class="space-y-1.5">
                                        @foreach($investCategories as $item)
                                            @php
                                                $txList = $item['transactions'] ?? [];
                                            @endphp
                                            <div class="bg-surface-container-high/60 rounded-xl overflow-hidden transition-all border border-outline-variant/10">
                                                <div
                                                    @click="expandedCat = (expandedCat === '{{ $item['id'] }}' ? null : '{{ $item['id'] }}')"
                                                    class="flex items-center justify-between p-3 text-xs cursor-pointer select-none hover:bg-surface-container-high transition-colors group"
                                                >
                                                    <div class="flex items-center gap-2.5 min-w-0">
                                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $item['color'] }}"></span>
                                                        <span class="font-bold text-on-surface group-hover:text-primary transition-colors truncate text-sm">{{ $item['name'] }}</span>
                                                        @if($item['tx_count'] > 0)
                                                            <span class="text-[10px] font-semibold text-on-surface-variant bg-surface-container-highest px-2 py-0.5 rounded-full shrink-0">
                                                                {{ $item['tx_count'] }} {{ $item['tx_count'] === 1 ? 'item' : 'itens' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-2.5 shrink-0 ml-2">
                                                        <span class="text-sm font-extrabold text-on-surface tabular-nums">
                                                            R$ {{ $item['spent_formatted'] }}
                                                        </span>
                                                        <div class="w-6 h-6 rounded-lg bg-surface-container-highest flex items-center justify-center text-on-surface-variant group-hover:text-primary group-hover:bg-primary/10 transition-colors">
                                                            <span
                                                                class="material-symbols-outlined text-[16px] transition-transform duration-200"
                                                                :class="expandedCat === '{{ $item['id'] }}' ? 'rotate-180 text-primary' : ''"
                                                            >expand_more</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div
                                                    x-show="expandedCat === '{{ $item['id'] }}'"
                                                    x-cloak
                                                    x-collapse
                                                    class="px-3 pb-3 pt-1 border-t border-outline-variant/10 space-y-2 bg-surface-container-lowest/40"
                                                >
                                                    @if(count($txList) === 0)
                                                        <p class="text-[0.6875rem] text-on-surface-variant py-2 text-center italic">Nenhum lançamento registrado no mês.</p>
                                                    @else
                                                        <div class="space-y-1 max-h-60 overflow-y-auto pr-1 divide-y divide-outline-variant/10">
                                                            @foreach($txList as $tx)
                                                                <a
                                                                    href="{{ route('transactions.create', ['editar' => $tx['id']]) }}"
                                                                    wire:navigate
                                                                    class="pt-1.5 first:pt-0 flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-surface-container-highest/60 transition-colors group/tx"
                                                                >
                                                                    <div class="flex items-center gap-2 min-w-0">
                                                                        <span class="text-[11px] font-semibold text-on-surface-variant/70 tabular-nums shrink-0">
                                                                            {{ $tx['date_formatted'] }}
                                                                        </span>
                                                                        @if($tx['bank_brand'])
                                                                            <x-bank-icon :brand="$tx['bank_brand']" size="xs" />
                                                                        @endif
                                                                        <span class="text-xs text-on-surface truncate group-hover/tx:text-primary transition-colors">
                                                                            {{ $tx['clean_title'] ?: $tx['description'] }}
                                                                        </span>
                                                                    </div>
                                                                    <span class="text-xs font-bold shrink-0 tabular-nums {{ $tx['is_income'] ? 'text-income' : 'text-expense' }}">
                                                                        {{ $tx['is_income'] ? '+' : '-' }} R$ {{ $tx['amount_formatted'] }}
                                                                    </span>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="text-[0.6875rem] text-on-surface-variant bg-surface-container-high/40 p-2.5 rounded-xl flex items-center justify-between">
                                    <span>Sobra líquida da sua renda de <strong>R$ {{ number_format($referenceSalaryCents / 100, 2, ',', '.') }}</strong> pronta para ser poupada/investida:</span>
                                    <span class="font-extrabold text-secondary-fixed-dim text-xs tabular-nums">R$ {{ number_format($investAvailableCents / 100, 2, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            </div>
        @endif

        <!-- Right Column: Category Budget Progress Cards -->
        @if($activeTab === 'all' || $activeTab === 'categorias')
            <section class="{{ $activeTab === 'categorias' ? 'lg:col-span-12' : 'lg:col-span-6' }} space-y-3.5 animate-stagger-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant block">Metas por Categoria</span>
                        <h3 class="text-base font-bold text-on-surface tracking-tight">Limites Operacionais</h3>
                    </div>
                    <button
                        type="button"
                        wire:click="toggleEditBudgets"
                        class="text-xs font-semibold text-primary hover:text-primary-fixed transition-colors flex items-center gap-1 bg-primary/10 px-3 py-1.5 rounded-xl tap-bounce"
                    >
                        <span class="material-symbols-outlined text-sm">{{ $editingBudgets ? 'close' : 'tune' }}</span>
                        <span>{{ $editingBudgets ? 'Cancelar' : 'Ajustar Metas' }}</span>
                    </button>
                </div>

                @if($editingBudgets)
                    <div class="bg-surface-container rounded-2xl p-4 ghost-border space-y-3 animate-pop">
                        <p class="text-xs font-bold text-on-surface">Defina o teto mensal (R$) para cada categoria:</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach($categoryLimits as $cat)
                                <div class="bg-surface-container-low p-3 rounded-xl ghost-border flex items-center justify-between gap-2">
                                    <span class="text-xs font-semibold text-on-surface truncate">{{ $cat['name'] }}</span>
                                    <div class="flex items-center gap-1 bg-surface-container-lowest px-2.5 py-1.5 rounded-lg border border-outline-variant/20">
                                        <span class="text-xs text-primary font-bold">R$</span>
                                        <input
                                            type="text"
                                            wire:model="budgetInputs.{{ $cat['id'] }}"
                                            class="w-20 bg-transparent border-none p-0 text-xs font-bold text-right text-on-surface focus:outline-none tabular-nums"
                                        />
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <button
                            type="button"
                            wire:click="saveBudgets"
                            class="w-full py-2.5 rounded-xl bg-primary text-on-primary text-xs font-extrabold hover:bg-primary-fixed transition-colors tap-bounce"
                        >
                            Salvar Metas Mensais
                        </button>
                    </div>
                @endif

                <div class="space-y-3" x-data="{ expandedCategory: null }">
                    @foreach($categoryLimits as $cat)
                        @php
                            $iconName = \App\Support\MaterialIcon::forCategory($cat['name'], 'expense');
                            $isExceeded = $cat['status'] === 'exceeded';
                            $isWarning = $cat['status'] === 'warning';
                            $txCount = count($cat['transactions'] ?? []);
                        @endphp
                        <div
                            class="bg-surface-container-low rounded-2xl p-4 {{ $isExceeded ? 'border border-error/25' : 'ghost-border' }} space-y-3 transition-all duration-200"
                            :class="expandedCategory === '{{ $cat['id'] }}' ? 'ring-1 ring-primary/40 bg-surface-container shadow-md' : 'hover:bg-surface-container/70'"
                        >
                            {{-- Cabeçalho Clicável da Categoria --}}
                            <div
                                @click="expandedCategory = (expandedCategory === '{{ $cat['id'] }}' ? null : '{{ $cat['id'] }}')"
                                class="flex items-center justify-between cursor-pointer select-none group"
                                title="Clique para ver os lançamentos desta categoria"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-10 h-10 rounded-xl {{ $isExceeded ? 'bg-error/15 text-error' : ($isWarning ? 'bg-secondary-container/25 text-secondary-fixed-dim' : 'bg-primary/15 text-primary') }} flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                                        <span class="material-symbols-outlined">{{ $iconName }}</span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <h4 class="text-sm font-bold text-on-surface group-hover:text-primary transition-colors truncate">{{ $cat['name'] }}</h4>
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-surface-container-highest text-on-surface-variant tabular-nums shrink-0">
                                                {{ $txCount }} {{ $txCount === 1 ? 'lançamento' : 'lançamentos' }}
                                            </span>
                                        </div>
                                        @if($isExceeded)
                                            <span class="text-[0.6875rem] text-error font-semibold flex items-center gap-1 mt-0.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span>
                                                Excedido (+R$ {{ number_format($cat['exceeded_cents'] / 100, 2, ',', '.') }})
                                            </span>
                                        @elseif($isWarning)
                                            <span class="text-[0.6875rem] text-secondary-fixed-dim font-semibold flex items-center gap-1 mt-0.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-secondary-fixed-dim"></span>
                                                Atenção ({{ $cat['pct'] }}% utilizado)
                                            </span>
                                        @else
                                            <span class="text-[0.6875rem] text-primary font-semibold flex items-center gap-1 mt-0.5">
                                                <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                                No Limite ({{ $cat['pct'] }}% utilizado)
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 shrink-0 ml-2">
                                    <div class="text-right">
                                        <span class="text-sm font-extrabold text-expense tabular-nums block">
                                            R$ {{ number_format($cat['spent_cents'] / 100, 2, ',', '.') }}
                                        </span>
                                        <span class="text-[0.6875rem] text-on-surface-variant tabular-nums">
                                            de R$ {{ number_format($cat['budget_cents'] / 100, 2, ',', '.') }}
                                            @if(! empty($cat['budget_percent']))
                                                <span class="text-[0.625rem] text-primary/80">({{ number_format($cat['budget_percent'], 1, ',', '.') }}%)</span>
                                            @endif
                                        </span>
                                    </div>

                                    <div class="w-7 h-7 rounded-lg bg-surface-container-high/60 group-hover:bg-primary/20 group-hover:text-primary text-on-surface-variant flex items-center justify-center transition-colors">
                                        <span class="material-symbols-outlined text-[18px] transition-transform duration-200" :class="expandedCategory === '{{ $cat['id'] }}' ? 'rotate-180 text-primary' : ''">expand_more</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Barra de Progresso --}}
                            <div class="w-full h-2 rounded-full bg-surface-container-highest overflow-hidden">
                                <div
                                    class="h-full rounded-full animate-bar-x {{ $isExceeded ? 'bg-gradient-to-r from-error to-error-container' : ($isWarning ? 'bg-gradient-to-r from-secondary-fixed-dim to-amber-500' : 'bg-gradient-to-r from-primary to-primary-container') }}"
                                    style="width: {{ min(100, max(3, $cat['pct'])) }}%"
                                ></div>
                            </div>

                            <div class="flex items-center justify-between text-[0.6875rem] text-on-surface-variant">
                                @if($isExceeded)
                                    <span>Meta ultrapassada em {{ $cat['pct'] - 100 }}%</span>
                                    <button type="button" wire:click.stop="toggleEditBudgets" class="text-error font-semibold hover:underline">Reajustar teto</button>
                                @else
                                    <span>Restam R$ {{ number_format($cat['remaining_cents'] / 100, 2, ',', '.') }} disponíveis</span>
                                    <span class="text-primary font-semibold">Dentro da meta</span>
                                @endif
                            </div>

                            {{-- Gaveta de Lançamentos Considerados --}}
                            <div
                                x-cloak
                                x-show="expandedCategory === '{{ $cat['id'] }}'"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-2"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-2"
                                class="pt-3 border-t border-outline-variant/20 space-y-2.5"
                            >
                                <div class="flex items-center justify-between pb-1 text-xs">
                                    <span class="font-bold text-on-surface flex items-center gap-1.5">
                                        <span class="material-symbols-outlined text-primary text-[17px]">receipt_long</span>
                                        <span>Transações consideradas ({{ $txCount }})</span>
                                    </span>
                                    <a
                                        href="{{ route('transactions', ['month' => $month, 'category' => ($cat['id'] === '__none__' ? '' : $cat['id'])]) }}"
                                        wire:navigate
                                        class="text-[11px] font-semibold text-primary hover:underline flex items-center gap-0.5 tap-bounce"
                                    >
                                        <span>Abrir no Extrato</span>
                                        <span class="material-symbols-outlined text-[14px]">arrow_forward</span>
                                    </a>
                                </div>

                                @if($txCount === 0)
                                    <div class="p-4 rounded-xl bg-surface-container-high/40 text-center text-xs text-on-surface-variant border border-outline-variant/10">
                                        Nenhum gasto contabilizado nesta categoria em {{ $currentMonthLabel }}.
                                    </div>
                                @else
                                    <div class="max-h-80 overflow-y-auto space-y-1.5 pr-1 divide-y divide-outline-variant/10 rounded-xl bg-surface-container-high/30 p-2 border border-outline-variant/15">
                                        @foreach($cat['transactions'] as $tx)
                                            <a
                                                href="{{ route('transactions.create', ['editar' => $tx['id']]) }}"
                                                wire:navigate
                                                class="pt-1.5 first:pt-0 flex items-center justify-between gap-2.5 hover:bg-surface-container-highest/60 p-2 rounded-lg transition-colors group cursor-pointer"
                                                title="Clique para editar transação"
                                            >
                                                <div class="flex items-center gap-2.5 min-w-0">
                                                    <span class="text-[11px] font-semibold text-on-surface-variant/70 w-9 shrink-0 tabular-nums">
                                                        {{ $tx['date_formatted'] }}
                                                    </span>
                                                    @if($tx['bank_brand'])
                                                        <x-bank-icon :brand="$tx['bank_brand']" size="sm" />
                                                    @endif
                                                    <div class="flex flex-col min-w-0">
                                                        <span class="text-xs font-semibold text-on-surface truncate group-hover:text-primary transition-colors">
                                                            {{ $tx['clean_title'] }}
                                                        </span>
                                                        <span class="text-[10px] text-on-surface-variant/70 truncate">
                                                            {{ $tx['account_name'] }}
                                                        </span>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-1.5 shrink-0 text-right">
                                                    <span class="text-xs font-bold tabular-nums {{ $tx['amount_cents'] >= 0 ? 'text-income' : 'text-expense' }}">
                                                        {{ $tx['amount_cents'] >= 0 ? '+' : '-' }}R$ {{ $tx['amount_formatted'] }}
                                                    </span>
                                                    <span class="material-symbols-outlined text-[13px] text-on-surface-variant/30 group-hover:text-primary transition-colors">edit</span>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>

    <!-- Report Export Footer -->
    <section class="pt-2">
        <div class="bg-surface-container-low rounded-2xl p-4 ghost-border flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-surface-container-high text-on-surface-variant flex items-center justify-center">
                    <span class="material-symbols-outlined">description</span>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-on-surface">Relatório Gerencial Completo</h4>
                    <p class="text-[0.6875rem] text-on-surface-variant">Exportar demonstrativo consolidado em CSV ou Impressão</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    wire:click="exportCsv"
                    class="px-3.5 py-2 rounded-xl bg-surface-container-high hover:bg-surface-container-highest text-xs font-bold text-primary transition-colors flex items-center gap-1.5 tap-bounce"
                >
                    <span class="material-symbols-outlined text-base">download</span>
                    <span>Baixar CSV</span>
                </button>
                <button
                    type="button"
                    onclick="window.print()"
                    class="px-3.5 py-2 rounded-xl bg-primary/15 hover:bg-primary/25 text-xs font-bold text-primary transition-colors flex items-center gap-1.5 tap-bounce"
                >
                    <span class="material-symbols-outlined text-base">print</span>
                    <span>Imprimir / PDF</span>
                </button>
            </div>
        </div>
    </section>
</div>

