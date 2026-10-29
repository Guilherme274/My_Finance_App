<div class="space-y-6 animate-page-enter">
    @if($toastMessage)
        <div class="bg-primary/15 border border-primary/30 rounded-2xl p-3.5 flex items-center justify-between gap-3 animate-pop">
            <div class="flex items-center gap-2.5">
                <span class="material-symbols-outlined text-primary">check_circle</span>
                <span class="text-xs font-bold text-on-surface">{{ $toastMessage }}</span>
            </div>
            <button type="button" wire:click="$set('toastMessage', null)" class="text-xs text-on-surface-variant hover:text-on-surface">Fechar</button>
        </div>
    @endif

    <!-- 1. Hero Portfolio Card -->
    <section aria-label="Patrimônio Investido" class="pt-1 animate-stagger-1">
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-surface-container-high via-surface-container to-surface-container-low p-6 ghost-border shadow-[0_20px_40px_rgba(0,0,0,0.4)]">
            <div class="absolute -top-24 -right-24 w-64 h-64 rounded-full bg-primary/10 blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-24 -left-24 w-56 h-56 rounded-full bg-tertiary/5 blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col gap-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                        <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant">Patrimônio Investido Consolidado</span>
                    </div>
                    <button type="button" wire:click="togglePrivacy" class="text-on-surface-variant hover:text-on-surface transition-colors p-1 rounded-lg hover:bg-surface-container-highest/50 tap-bounce" title="Ocultar/Mostrar valores">
                        <span class="material-symbols-outlined text-lg">{{ $hideBalances ? 'visibility_off' : 'visibility' }}</span>
                    </button>
                </div>

                <div class="flex flex-col gap-1.5">
                    <div class="flex items-baseline gap-2">
                        <span class="text-lg font-bold text-primary">R$</span>
                        <h2 class="text-display-lg-mobile sm:text-4xl font-extrabold tracking-tight text-on-surface tabular-nums">
                            {{ $hideBalances ? '••••••' : number_format($totalPortfolioCents / 100, 2, ',', '.') }}
                        </h2>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-primary-container/25 text-income border border-primary/20">
                            <span class="material-symbols-outlined text-sm">trending_up</span>
                            +R$ {{ $hideBalances ? '••••' : number_format($monthlyProfitEstimateCents / 100, 2, ',', '.') }} (+{{ number_format($weightedMonthlyYield, 2, ',', '.') }}%)
                        </span>
                        <span class="text-xs text-on-surface-variant">rendimento estimado no mês</span>
                    </div>
                </div>

                <!-- Performance Metrics Row -->
                <div class="grid grid-cols-3 gap-2 pt-3 border-t border-outline-variant/15">
                    <div>
                        <span class="text-[0.625rem] uppercase tracking-wider text-on-surface-variant block">Lucro Acumulado</span>
                        <span class="text-sm font-extrabold text-income tabular-nums mt-0.5 block">
                            +R$ {{ $hideBalances ? '••••' : number_format($accumulatedProfitCents / 100, 2, ',', '.') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[0.625rem] uppercase tracking-wider text-on-surface-variant block">Rentabilidade (12m)</span>
                        <span class="text-sm font-extrabold text-on-surface tabular-nums mt-0.5 block">
                            +{{ number_format($weightedMonthlyYield * 12.4, 2, ',', '.') }}%
                        </span>
                    </div>
                    <div>
                        <span class="text-[0.625rem] uppercase tracking-wider text-on-surface-variant block">Benchmark</span>
                        <span class="text-sm font-extrabold text-tertiary tabular-nums mt-0.5 block">112% do CDI</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Left Column: Emergency Reserve + Allocation Donut -->
        <div class="lg:col-span-6 space-y-6 animate-stagger-2">
            <!-- 2. Emergency Reserve Module -->
            <section aria-label="Reserva de Emergência" class="bg-surface-container-low rounded-2xl p-5 ghost-border relative overflow-hidden space-y-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-primary/15 text-primary flex items-center justify-center border border-primary/20">
                            <span class="material-symbols-outlined text-2xl" style="font-variation-settings: 'FILL' 1;">shield_with_heart</span>
                        </div>
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-base font-bold text-on-surface tracking-tight">Reserva de Emergência</h3>
                                <span class="text-[0.625rem] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full bg-primary/15 text-primary">Liquidez D+0</span>
                            </div>
                            <p class="text-xs text-on-surface-variant">{{ $reserve->subtitle ?? '6 meses de custo de vida protegido em Tesouro Selic / CDB 100%' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Numbers & Bar -->
                <div class="space-y-2 pt-1">
                    <div class="flex items-baseline justify-between">
                        <div>
                            <span class="text-2xl font-extrabold text-on-surface tabular-nums">
                                R$ {{ $hideBalances ? '••••••' : number_format($reserveBalanceCents / 100, 2, ',', '.') }}
                            </span>
                            <span class="text-xs text-on-surface-variant ml-1">
                                / R$ {{ $hideBalances ? '••••••' : number_format($reserveTargetCents / 100, 2, ',', '.') }}
                            </span>
                        </div>
                        <span class="text-sm font-extrabold text-primary tabular-nums">{{ $reservePercent }}%</span>
                    </div>
                    <div class="w-full h-3 rounded-full bg-surface-container-highest overflow-hidden p-0.5">
                        <div class="h-full rounded-full bg-gradient-to-r from-primary-container to-primary shadow-[0_0_12px_rgba(78,222,163,0.4)] animate-bar-x" style="width: {{ $reservePercent }}%"></div>
                    </div>
                </div>

                <!-- Reserve Details Grid -->
                <div class="grid grid-cols-3 gap-2.5 pt-1">
                    <div class="bg-surface-container rounded-xl p-2.5">
                        <span class="text-[0.625rem] uppercase tracking-wider text-on-surface-variant block">Meses Cobertos</span>
                        <span class="text-xs font-extrabold text-on-surface mt-0.5 block">{{ number_format($monthsCovered, 1, ',', '.') }} de 6 meses</span>
                    </div>
                    <div class="bg-surface-container rounded-xl p-2.5">
                        <span class="text-[0.625rem] uppercase tracking-wider text-on-surface-variant block">Rendimento Mensal</span>
                        <span class="text-xs font-extrabold text-income mt-0.5 block">+R$ {{ number_format($reserveMonthlyYieldCents / 100, 2, ',', '.') }}</span>
                    </div>
                    <div class="bg-surface-container rounded-xl p-2.5">
                        <span class="text-[0.625rem] uppercase tracking-wider text-on-surface-variant block">Faltam p/ Meta</span>
                        <span class="text-xs font-extrabold text-tertiary mt-0.5 block">R$ {{ number_format($reserveMissingCents / 100, 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- Action CTA -->
                <div class="pt-1 flex items-center justify-between gap-3">
                    <span class="text-[0.6875rem] text-on-surface-variant">
                        @if($reserveMissingCents > 0)
                            Faltam <strong class="text-on-surface">R$ {{ number_format($reserveMissingCents / 100, 2, ',', '.') }}</strong> para blindagem total
                        @else
                            <strong class="text-primary">Meta de blindagem atingida!</strong>
                        @endif
                    </span>
                    <button
                        type="button"
                        wire:click="openReserveContribution"
                        class="px-3.5 py-2 rounded-xl bg-primary/15 hover:bg-primary/25 text-primary text-xs font-bold transition-colors flex items-center gap-1.5 shrink-0 tap-bounce"
                    >
                        <span class="material-symbols-outlined text-base">add_circle</span>
                        <span>Aportar na Reserva</span>
                    </button>
                </div>
            </section>

            <!-- 3. Portfolio Allocation Donut Chart -->
            <section aria-label="Alocação da Carteira" class="bg-surface-container-low rounded-2xl p-5 ghost-border space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant block">Diversificação</span>
                        <h3 class="text-base font-bold text-on-surface tracking-tight">Alocação por Classe</h3>
                    </div>
                    <span class="text-xs font-semibold text-on-surface-variant bg-surface-container-high px-2.5 py-1 rounded-lg">
                        {{ $investments->count() }} Ativos
                    </span>
                </div>

                <div class="grid grid-cols-12 gap-4 items-center pt-1">
                    <!-- SVG Donut Chart -->
                    <div class="col-span-5 flex items-center justify-center relative">
                        <svg class="w-32 h-32 -rotate-90 transform" viewBox="0 0 100 100">
                            <circle cx="50" cy="50" r="38" fill="transparent" stroke="#222a3d" stroke-width="12"></circle>
                            @foreach($allocations as $alloc)
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="38"
                                    fill="transparent"
                                    stroke="{{ $alloc['stroke'] }}"
                                    stroke-width="12"
                                    stroke-dasharray="{{ $alloc['dasharray'] }}"
                                    stroke-dashoffset="{{ $alloc['dashoffset'] }}"
                                ></circle>
                            @endforeach
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                            <span class="text-[0.6rem] uppercase tracking-wider text-on-surface-variant font-semibold">Risco</span>
                            <span class="text-xs font-extrabold text-on-surface">Moderado</span>
                        </div>
                    </div>

                    <!-- Allocation Legend -->
                    <div class="col-span-7 space-y-2.5">
                        @foreach($allocations as $alloc)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-2.5 h-2.5 rounded-full {{ $alloc['dot'] }} shrink-0"></span>
                                    <span class="text-xs font-semibold text-on-surface truncate">{{ $alloc['label'] }}</span>
                                </div>
                                <div class="text-right shrink-0 ml-2">
                                    <span class="text-xs font-bold text-on-surface tabular-nums">{{ $alloc['pct'] }}%</span>
                                    <span class="text-[0.65rem] text-on-surface-variant block tabular-nums">
                                        R$ {{ $hideBalances ? '••••' : number_format($alloc['balance_cents'] / 100, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        </div>

        <!-- Right Column: Holdings List & Dividends -->
        <div class="lg:col-span-6 space-y-6 animate-stagger-3">
            <!-- 4. Grouped Holdings List -->
            <section aria-label="Ativos na Carteira" class="space-y-3.5">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-[0.6875rem] font-semibold uppercase tracking-[0.08em] text-on-surface-variant block">Meus Ativos</span>
                        <h3 class="text-base font-bold text-on-surface tracking-tight">Posições em Carteira</h3>
                    </div>
                    <button
                        type="button"
                        wire:click="openNewAssetModal('fixed_income')"
                        class="text-xs font-semibold text-primary hover:text-primary-fixed transition-colors flex items-center gap-1 bg-primary/10 px-3 py-1.5 rounded-xl tap-bounce"
                    >
                        <span class="material-symbols-outlined text-sm">add</span>
                        <span>Novo Aporte / Ativo</span>
                    </button>
                </div>

                <div class="space-y-2.5">
                    @foreach($holdings as $asset)
                        @php
                            $classColors = [
                                'fixed_income' => 'bg-primary/15 text-primary',
                                'stocks_fii' => 'bg-tertiary/15 text-tertiary',
                                'crypto_global' => 'bg-secondary-container/25 text-secondary-fixed-dim',
                                'emergency' => 'bg-purple-500/15 text-purple-300',
                            ];
                            $badgeColor = $classColors[$asset->asset_class] ?? 'bg-primary/15 text-primary';
                            $classMetaInfo = \App\Models\Investment::CLASSES[$asset->asset_class] ?? ['short' => 'Ativo'];
                        @endphp
                        <div class="bg-surface-container-low hover:bg-surface-container transition-all rounded-2xl p-4 ghost-border flex items-center justify-between group hover-lift">
                            <div class="flex items-center gap-3.5 min-w-0">
                                <div class="w-11 h-11 rounded-xl {{ $badgeColor }} flex items-center justify-center shrink-0">
                                    <span class="material-symbols-outlined">{{ $asset->icon ?: 'monitoring' }}</span>
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="text-sm font-bold text-on-surface group-hover:text-primary transition-colors truncate">{{ $asset->name }}</h4>
                                        <span class="text-[0.625rem] font-bold px-2 py-0.5 rounded bg-surface-container-highest text-on-surface-variant">
                                            {{ $asset->ticker ?: $classMetaInfo['short'] }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant mt-0.5 truncate">
                                        {{ $asset->subtitle ?: 'Carteira Principal' }}
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0 ml-3">
                                <span class="text-sm font-extrabold text-on-surface tabular-nums block">
                                    R$ {{ $hideBalances ? '••••••' : number_format($asset->balance_cents / 100, 2, ',', '.') }}
                                </span>
                                <div class="flex items-center justify-end gap-2 mt-0.5">
                                    <span class="text-[0.6875rem] font-semibold text-primary tabular-nums">
                                        +{{ number_format((float) $asset->daily_change_pct, 2, ',', '.') }}% mês
                                    </span>
                                    <button type="button" wire:click="openContributionFor('{{ $asset->id }}')" class="text-[0.65rem] font-bold text-primary hover:underline" title="Novo Aporte">
                                        +Aportar
                                    </button>
                                    <button type="button" wire:click="editAsset('{{ $asset->id }}')" class="text-[0.65rem] text-on-surface-variant hover:text-on-surface" title="Editar">
                                        Editar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            <!-- 5. Upcoming Dividends / Yield Banner -->
            <section class="pt-1">
                <div class="bg-gradient-to-r from-surface-container-low via-surface-container to-surface-container-low rounded-2xl p-4 ghost-border flex items-center justify-between">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl bg-primary/15 text-primary flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined">payments</span>
                        </div>
                        <div>
                            <h4 class="text-xs font-bold text-on-surface">Proventos &amp; Dividendos Estimados</h4>
                            <p class="text-[0.6875rem] text-on-surface-variant">Dividendos isentos de FIIs + Juros sobre Capital</p>
                        </div>
                    </div>
                    <div class="text-right shrink-0 ml-3">
                        <span class="text-sm font-extrabold text-income tabular-nums block">
                            +R$ {{ $hideBalances ? '••••' : number_format(($monthlyProfitEstimateCents / 100) * 0.22, 2, ',', '.') }}
                        </span>
                        <span class="text-[0.625rem] text-on-surface-variant">Isento de IR</span>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modal: Quick Contribution -->
    @if($showContributionModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm animate-page-enter">
            <div class="bg-surface-container-low rounded-2xl p-6 w-full max-w-md ghost-border shadow-2xl space-y-4 animate-pop">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-on-surface">Realizar Novo Aporte</h3>
                    <button type="button" wire:click="$set('showContributionModal', false)" class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-on-surface-variant hover:text-on-surface">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-on-surface-variant block">Valor do Aporte (R$)</label>
                    <input
                        type="text"
                        wire:model="contributionAmountInput"
                        class="w-full rounded-xl bg-surface-container-lowest border border-outline-variant/25 px-4 py-3 text-lg font-extrabold text-primary focus:outline-none focus:border-primary tabular-nums"
                    />
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="$set('showContributionModal', false)" class="px-4 py-2.5 rounded-xl bg-surface-container-high text-xs font-bold text-on-surface-variant hover:text-on-surface">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmContribution" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary text-xs font-extrabold hover:bg-primary-fixed tap-bounce">
                        Confirmar Aporte
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal: New / Edit Asset -->
    @if($showNewAssetModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-sm animate-page-enter">
            <div class="bg-surface-container-low rounded-2xl p-6 w-full max-w-md ghost-border shadow-2xl space-y-4 animate-pop">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-on-surface">{{ $editingAssetId ? 'Editar Ativo' : 'Novo Ativo na Carteira' }}</h3>
                    <button type="button" wire:click="$set('showNewAssetModal', false)" class="w-8 h-8 rounded-lg bg-surface-container-high flex items-center justify-center text-on-surface-variant hover:text-on-surface">
                        <span class="material-symbols-outlined text-lg">close</span>
                    </button>
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="text-[0.7rem] font-bold uppercase text-on-surface-variant block mb-1">Nome do Ativo</label>
                        <input type="text" wire:model="assetName" placeholder="Ex: Tesouro IPCA+ 2035, HGLG11, CDB Nubank" class="w-full rounded-xl bg-surface-container-lowest border border-outline-variant/25 px-3.5 py-2.5 text-sm text-on-surface focus:outline-none focus:border-primary" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[0.7rem] font-bold uppercase text-on-surface-variant block mb-1">Classe</label>
                            <select wire:model="assetClass" class="w-full rounded-xl bg-surface-container-lowest border border-outline-variant/25 px-3 py-2.5 text-xs font-semibold text-on-surface focus:outline-none focus:border-primary">
                                @foreach(\App\Models\Investment::CLASSES as $key => $meta)
                                    <option value="{{ $key }}">{{ $meta['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[0.7rem] font-bold uppercase text-on-surface-variant block mb-1">Subtítulo / Liquidez</label>
                            <input type="text" wire:model="assetSubtitle" class="w-full rounded-xl bg-surface-container-lowest border border-outline-variant/25 px-3 py-2.5 text-xs text-on-surface focus:outline-none focus:border-primary" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-[0.7rem] font-bold uppercase text-on-surface-variant block mb-1">Saldo Atual (R$)</label>
                            <input type="text" wire:model="assetBalanceInput" class="w-full rounded-xl bg-surface-container-lowest border border-outline-variant/25 px-3 py-2.5 text-sm font-bold text-primary tabular-nums focus:outline-none focus:border-primary" />
                        </div>
                        <div>
                            <label class="text-[0.7rem] font-bold uppercase text-on-surface-variant block mb-1">Rentab. Mensal (%)</label>
                            <input type="text" wire:model="assetYieldInput" class="w-full rounded-xl bg-surface-container-lowest border border-outline-variant/25 px-3 py-2.5 text-sm font-bold text-on-surface tabular-nums focus:outline-none focus:border-primary" />
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-2">
                    @if($editingAssetId)
                        <button type="button" wire:click="deleteAsset('{{ $editingAssetId }}')" class="text-xs font-bold text-error hover:underline">
                            Excluir ativo
                        </button>
                    @else
                        <span></span>
                    @endif
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="$set('showNewAssetModal', false)" class="px-4 py-2.5 rounded-xl bg-surface-container-high text-xs font-bold text-on-surface-variant hover:text-on-surface">
                            Cancelar
                        </button>
                        <button type="button" wire:click="saveAsset" class="px-5 py-2.5 rounded-xl bg-primary text-on-primary text-xs font-extrabold hover:bg-primary-fixed tap-bounce">
                            Salvar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

