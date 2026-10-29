<div class="max-w-xl mx-auto space-y-6 animate-sheet-up pb-8">
    <!-- Subheader Action Bar -->
    <div class="flex items-center justify-between pt-1">
        <a href="{{ route('dashboard') }}" class="w-10 h-10 rounded-xl bg-surface-container-low hover:bg-surface-container-high flex items-center justify-center text-on-surface-variant hover:text-on-surface transition-colors ghost-border tap-bounce" title="Voltar">
            <span class="material-symbols-outlined text-xl">close</span>
        </a>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full {{ $type === 'income' ? 'bg-primary' : ($type === 'transfer' ? 'bg-tertiary' : 'bg-error') }} animate-pulse"></span>
            <h1 class="text-base font-bold tracking-tight text-on-surface">
                {{ $editingId ? 'Editar Transação' : 'Nova Transação' }}
            </h1>
        </div>
        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            class="text-xs font-bold px-3.5 py-2 rounded-xl transition-all tap-bounce flex items-center gap-1.5 {{ $justSaved ? 'bg-primary text-on-primary shadow-[0_0_16px_rgba(78,222,163,0.4)]' : 'text-primary hover:text-primary-fixed hover:bg-primary/10' }}"
        >
            <span class="material-symbols-outlined text-sm">{{ $justSaved ? 'done_all' : 'check' }}</span>
            <span>{{ $justSaved ? 'Salvo!' : 'Salvar' }}</span>
        </button>
    </div>

    @if($toastMessage)
        <div class="bg-primary/15 border border-primary/30 rounded-2xl p-4 flex items-center justify-between gap-3 animate-pop shadow-[0_12px_30px_rgba(16,185,129,0.18)]">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-primary text-on-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-xl">check_circle</span>
                </div>
                <div>
                    <p class="text-sm font-bold text-on-surface">{{ $toastMessage }}</p>
                    <p class="text-xs text-on-surface-variant">Sincronizado automaticamente com seu painel.</p>
                </div>
            </div>
            <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-xl bg-surface-container-high text-xs font-bold text-primary hover:bg-surface-container-highest transition-colors shrink-0">
                Ver Painel
            </a>
        </div>
    @endif

    <!-- 1. Transaction Type Selector (Segmented Pill) -->
    <section aria-label="Tipo de Transação" class="bg-surface-container-low p-1.5 rounded-2xl ghost-border grid grid-cols-3 gap-1.5">
        <button type="button" wire:click="setType('expense')"
            class="flex items-center justify-center gap-2 py-3 px-3 rounded-xl font-bold text-xs tracking-wide transition-all tap-bounce
                {{ $type === 'expense'
                    ? 'bg-surface-container-high text-error shadow-sm border border-error/20'
                    : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container/50' }}">
            <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' {{ $type === 'expense' ? 1 : 0 }};">south_west</span>
            <span>Despesa</span>
        </button>
        <button type="button" wire:click="setType('income')"
            class="flex items-center justify-center gap-2 py-3 px-3 rounded-xl font-bold text-xs tracking-wide transition-all tap-bounce
                {{ $type === 'income'
                    ? 'bg-surface-container-high text-primary shadow-sm border border-primary/25'
                    : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container/50' }}">
            <span class="material-symbols-outlined text-base" style="font-variation-settings: 'FILL' {{ $type === 'income' ? 1 : 0 }};">north_east</span>
            <span>Receita</span>
        </button>
        <button type="button" wire:click="setType('transfer')"
            class="flex items-center justify-center gap-2 py-3 px-3 rounded-xl font-bold text-xs tracking-wide transition-all tap-bounce
                {{ $type === 'transfer'
                    ? 'bg-surface-container-high text-tertiary shadow-sm border border-tertiary/25'
                    : 'text-on-surface-variant hover:text-on-surface hover:bg-surface-container/50' }}">
            <span class="material-symbols-outlined text-base">swap_horiz</span>
            <span>Transf.</span>
        </button>
    </section>

    <!-- 2. Hero Numeric Value Display & Quick Keypad -->
    <section aria-label="Valor da Transação" class="relative overflow-hidden rounded-2xl bg-surface-container-low p-6 ghost-border text-center">
        <div class="absolute -top-16 left-1/2 -translate-x-1/2 w-56 h-32 {{ $type === 'income' ? 'bg-primary/10' : ($type === 'transfer' ? 'bg-tertiary/10' : 'bg-error/10') }} blur-3xl pointer-events-none"></div>

        <p class="text-[0.6875rem] font-semibold uppercase tracking-[0.12em] text-on-surface-variant mb-2">
            {{ $type === 'income' ? 'Valor da Receita' : ($type === 'transfer' ? 'Valor da Transferência' : 'Valor da Despesa') }}
        </p>

        <div class="flex items-baseline justify-center gap-2 my-1">
            <span class="text-xl font-bold {{ $type === 'income' ? 'text-income' : ($type === 'transfer' ? 'text-tertiary' : 'text-expense') }}">R$</span>
            <input
                type="text"
                wire:model.live.debounce.250ms="amountInput"
                inputmode="numeric"
                class="bg-transparent border-none text-center font-extrabold text-4xl sm:text-5xl tracking-[-0.03em] {{ $type === 'income' ? 'text-income' : ($type === 'transfer' ? 'text-on-surface' : 'text-expense') }} tabular-nums focus:outline-none focus:ring-0 w-60"
                placeholder="0,00"
            />
            <button type="button" wire:click="toggleKeypad" class="w-8 h-8 rounded-lg bg-surface-container-high text-on-surface-variant hover:text-primary flex items-center justify-center transition-colors tap-bounce" title="Alternar teclado numérico">
                <span class="material-symbols-outlined text-lg">{{ $showKeypad ? 'keyboard_hide' : 'dialpad' }}</span>
            </button>
        </div>

        @error('amountCents')
            <p class="text-xs text-error font-semibold mt-1">{{ $message }}</p>
        @enderror

        <!-- Quick Value Steppers & Clear -->
        <div class="flex items-center justify-center gap-2 mt-4 flex-wrap">
            <button type="button" wire:click="stepAmount(-10)" class="px-3 py-1 rounded-full bg-surface-container-high hover:bg-surface-container-highest text-[0.75rem] font-semibold text-on-surface-variant hover:text-on-surface transition-colors tap-bounce">
                - R$ 10
            </button>
            <button type="button" wire:click="stepAmount(10)" class="px-3 py-1 rounded-full bg-surface-container-high hover:bg-surface-container-highest text-[0.75rem] font-semibold text-on-surface-variant hover:text-on-surface transition-colors tap-bounce">
                + R$ 10
            </button>
            <button type="button" wire:click="stepAmount(50)" class="px-3 py-1 rounded-full bg-surface-container-high hover:bg-surface-container-highest text-[0.75rem] font-semibold text-on-surface-variant hover:text-on-surface transition-colors tap-bounce">
                + R$ 50
            </button>
            <button type="button" wire:click="stepAmount(100)" class="px-3 py-1 rounded-full bg-surface-container-high hover:bg-surface-container-highest text-[0.75rem] font-semibold text-on-surface-variant hover:text-on-surface transition-colors tap-bounce">
                + R$ 100
            </button>
            <button type="button" wire:click="pressDigit('C')" class="px-3 py-1 rounded-full bg-error/10 hover:bg-error/20 text-[0.75rem] font-semibold text-error transition-colors tap-bounce">
                Limpar
            </button>
        </div>

        <!-- Collapsible Tactile Mini Numpad -->
        @if($showKeypad)
            <div class="grid grid-cols-3 gap-2 mt-5 pt-4 border-t border-outline-variant/15 animate-pop">
                @foreach(['1','2','3','4','5','6','7','8','9','C','0','BACK'] as $key)
                    <button
                        type="button"
                        wire:click="pressDigit('{{ $key }}')"
                        class="h-12 rounded-xl font-bold text-base flex items-center justify-center transition-all tap-bounce
                            {{ $key === 'C' ? 'bg-error/15 text-error hover:bg-error/25' : ($key === 'BACK' ? 'bg-surface-container-highest text-on-surface-variant hover:text-on-surface' : 'bg-surface-container hover:bg-surface-container-high text-on-surface') }}"
                    >
                        @if($key === 'BACK')
                            <span class="material-symbols-outlined text-lg">backspace</span>
                        @else
                            {{ $key }}
                        @endif
                    </button>
                @endforeach
            </div>
        @endif
    </section>

    <!-- 3. Category Grid Selector -->
    <section aria-label="Categoria" class="space-y-3">
        <div class="flex items-center justify-between px-1">
            <label class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-on-surface-variant">Categoria</label>
            <a href="{{ route('categories') }}" class="text-xs font-semibold text-primary hover:underline flex items-center gap-1">
                <span>Personalizar</span>
                <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        </div>
        <div class="grid grid-cols-4 gap-2.5">
            @foreach($categories as $cat)
                @php
                    $isSelectedCat = $categoryId === $cat->id;
                    $catIcon = \App\Support\MaterialIcon::forCategory($cat->name, $cat->kind);
                @endphp
                <button
                    type="button"
                    wire:click="selectCategory('{{ $cat->id }}')"
                    class="group flex flex-col items-center justify-center gap-2 p-3 rounded-2xl transition-all tap-bounce
                        {{ $isSelectedCat
                            ? 'bg-surface-container-high border border-primary/50 shadow-[0_0_20px_rgba(16,185,129,0.18)] scale-[1.02]'
                            : 'bg-surface-container-low hover:bg-surface-container ghost-border' }}"
                >
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center transition-colors
                        {{ $isSelectedCat
                            ? 'bg-primary/20 text-primary'
                            : 'bg-surface-container-high text-on-surface-variant group-hover:text-on-surface' }}">
                        <span class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' {{ $isSelectedCat ? 1 : 0 }};">{{ $catIcon }}</span>
                    </div>
                    <span class="text-[0.7rem] {{ $isSelectedCat ? 'font-bold text-on-surface' : 'font-medium text-on-surface-variant group-hover:text-on-surface' }} tracking-tight truncate w-full text-center">
                        {{ $cat->name }}
                    </span>
                </button>
            @endforeach
        </div>
    </section>

    <!-- 4. Description, Date & Payment Method Form Stack -->
    <section aria-label="Detalhes da Transação" class="bg-surface-container-low rounded-2xl p-4 ghost-border space-y-4">
        <!-- Description Input -->
        <div class="space-y-1.5">
            <label class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-on-surface-variant block px-1" for="tx-desc">
                Descrição &amp; Detalhes
            </label>
            <div class="relative flex items-center bg-surface-container-lowest rounded-xl px-3.5 py-3 border border-outline-variant/20 focus-within:border-primary transition-colors">
                <span class="material-symbols-outlined text-on-surface-variant text-xl mr-3">edit_note</span>
                <input
                    id="tx-desc"
                    type="text"
                    wire:model="description"
                    class="w-full bg-transparent border-none p-0 text-sm font-medium text-on-surface placeholder:text-on-surface-variant/50 focus:ring-0 focus:outline-none"
                    placeholder="Ex: Jantar no restaurante, Supermercado, Salário..."
                />
            </div>
        </div>

        <!-- Date Selector Chips -->
        <div class="space-y-1.5">
            <label class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-on-surface-variant block px-1">
                Data do Lançamento
            </label>
            <div class="grid grid-cols-3 gap-2">
                <button type="button" wire:click="selectDatePreset('today')"
                    class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition-all tap-bounce
                        {{ $datePreset === 'today' ? 'bg-primary/15 border border-primary/40 text-primary' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface ghost-border' }}">
                    <span class="material-symbols-outlined text-base">today</span>
                    <span>Hoje</span>
                </button>
                <button type="button" wire:click="selectDatePreset('yesterday')"
                    class="flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-semibold transition-all tap-bounce
                        {{ $datePreset === 'yesterday' ? 'bg-primary/15 border border-primary/40 text-primary font-bold' : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant hover:text-on-surface ghost-border' }}">
                    <span class="material-symbols-outlined text-base">history</span>
                    <span>Ontem</span>
                </button>
                <div class="relative">
                    <input
                        type="date"
                        wire:model.live="occurredOn"
                        class="w-full h-full py-2 px-2.5 rounded-xl text-xs font-semibold bg-surface-container hover:bg-surface-container-high text-on-surface ghost-border focus:outline-none focus:border-primary"
                    />
                </div>
            </div>
        </div>

        <!-- Account / Payment Method Selector -->
        <div class="space-y-1.5">
            <label class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-on-surface-variant block px-1">
                {{ $type === 'transfer' ? 'Conta de Origem' : 'Conta / Método de Pagamento' }}
            </label>
            <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                @foreach($accounts as $acc)
                    @php
                        $isSelectedAcc = $accountId === $acc->id;
                    @endphp
                    <button
                        type="button"
                        wire:click="selectAccount('{{ $acc->id }}')"
                        class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl shrink-0 transition-all tap-bounce
                            {{ $isSelectedAcc
                                ? 'bg-surface-container-high border border-primary/40 text-on-surface shadow-sm'
                                : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant ghost-border' }}"
                    >
                        <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $acc->color ?: '#4edea3' }}"></span>
                        <div class="text-left">
                            <p class="text-xs font-bold leading-none {{ $isSelectedAcc ? 'text-on-surface' : '' }}">{{ $acc->name }}</p>
                            <p class="text-[0.625rem] text-on-surface-variant mt-0.5">
                                {{ $acc->type === 'credit_card' ? 'Cartão de Crédito' : ($acc->type === 'Savings' || $acc->type === 'investment' ? 'Investimento' : 'Conta Corrente') }}
                            </p>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>

        @if($type === 'transfer')
            <div class="space-y-1.5 pt-2 border-t border-outline-variant/15">
                <label class="text-[0.6875rem] font-bold uppercase tracking-[0.08em] text-on-surface-variant block px-1">
                    Conta de Destino
                </label>
                <div class="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                    @foreach($accounts as $acc)
                        @if($acc->id !== $accountId)
                            <button
                                type="button"
                                wire:click="$set('destinationAccountId', '{{ $acc->id }}')"
                                class="flex items-center gap-2.5 px-3.5 py-2.5 rounded-xl shrink-0 transition-all tap-bounce
                                    {{ $destinationAccountId === $acc->id
                                        ? 'bg-surface-container-high border border-tertiary/40 text-on-surface'
                                        : 'bg-surface-container hover:bg-surface-container-high text-on-surface-variant ghost-border' }}"
                            >
                                <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $acc->color ?: '#69daff' }}"></span>
                                <span class="text-xs font-bold">{{ $acc->name }}</span>
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Installment Option (for Expenses when creating new) -->
        @if($type === 'expense' && ! $editingId)
            <div class="pt-2 border-t border-outline-variant/15 space-y-2.5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-on-surface-variant text-lg">calendar_view_month</span>
                        <span class="text-xs font-semibold text-on-surface">Parcelamento</span>
                    </div>
                    <span class="text-xs font-bold text-expense tabular-nums">
                        {{ $installments }}x de R$ {{ number_format($installmentCents / 100, 2, ',', '.') }}
                        {{ $installments === 1 ? '(À vista)' : '(Sem juros)' }}
                    </span>
                </div>
                <input
                    type="range"
                    wire:model.live="installments"
                    min="1"
                    max="12"
                    class="w-full accent-emerald-400 bg-surface-container-highest h-1.5 rounded-lg cursor-pointer"
                />
                <div class="flex justify-between text-[0.625rem] font-semibold text-on-surface-variant px-0.5">
                    <span>1x</span>
                    <span>3x</span>
                    <span>6x</span>
                    <span>9x</span>
                    <span>12x</span>
                </div>
            </div>
        @endif
    </section>

    <!-- 5. Receipt / Attachment Dropzone -->
    <section aria-label="Comprovante">
        <label class="w-full rounded-2xl border border-dashed border-outline-variant/30 bg-surface-container-low/60 hover:bg-surface-container-low p-4 flex items-center justify-between transition-colors group cursor-pointer">
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 rounded-xl bg-surface-container-high flex items-center justify-center text-on-surface-variant group-hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-xl">receipt_long</span>
                </div>
                <div class="text-left">
                    <p class="text-xs font-bold text-on-surface group-hover:text-primary transition-colors">
                        @if($receiptFile)
                            Arquivo selecionado: {{ $receiptFile->getClientOriginalName() }}
                        @elseif($existingReceiptPath)
                            Comprovante anexado (clique para substituir)
                        @else
                            Anexar Comprovante ou Nota Fiscal
                        @endif
                    </p>
                    <p class="text-[0.6875rem] text-on-surface-variant">Foto, PDF ou imagem da transferência</p>
                </div>
            </div>
            <span class="px-2.5 py-1 rounded-lg bg-surface-container-high text-[0.6875rem] font-semibold text-on-surface-variant group-hover:text-on-surface">
                {{ $receiptFile || $existingReceiptPath ? 'Alterar' : 'Opcional' }}
            </span>
            <input type="file" wire:model="receiptFile" accept="image/*,.pdf" class="hidden" />
        </label>
    </section>

    <!-- 6. Primary CTA Action & Inline Visual Confirmation -->
    <div class="pt-1 space-y-3">
        @if($toastMessage)
            <div class="bg-primary/15 border border-primary/40 rounded-2xl p-4 flex items-center justify-between gap-3 animate-pop shadow-[0_12px_30px_rgba(16,185,129,0.22)]">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-primary text-on-primary flex items-center justify-center shrink-0">
                        <span class="material-symbols-outlined text-xl">done_all</span>
                    </div>
                    <div>
                        <p class="text-sm font-extrabold text-primary">{{ $toastMessage }}</p>
                        <p class="text-xs text-on-surface-variant">As alterações já foram gravadas e sincronizadas.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <a href="{{ route('transactions') }}" class="px-3 py-1.5 rounded-xl bg-surface-container-high text-xs font-bold text-on-surface hover:text-primary transition-colors">
                        Ver Extrato
                    </a>
                    <a href="{{ route('dashboard') }}" class="px-3 py-1.5 rounded-xl bg-primary text-xs font-extrabold text-on-primary hover:brightness-110 transition-all">
                        Painel
                    </a>
                </div>
            </div>
        @endif

        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            class="w-full py-4 px-6 rounded-2xl font-extrabold text-sm tracking-wide transition-all flex items-center justify-center gap-2.5 tap-bounce
                {{ $justSaved
                    ? 'bg-primary text-on-primary ring-4 ring-primary/35 shadow-[0_0_35px_rgba(78,222,163,0.25)] scale-[1.01]'
                    : 'bg-primary text-on-primary shadow-md shadow-primary/20 active:scale-[0.99]' }}"
        >
            <span wire:loading.remove wire:target="save" class="material-symbols-outlined text-xl" style="font-variation-settings: 'FILL' 1;">
                {{ $justSaved ? 'done_all' : 'check_circle' }}
            </span>
            <span wire:loading wire:target="save" class="material-symbols-outlined text-xl animate-spin">
                progress_activity
            </span>
            <span wire:loading.remove wire:target="save">
                @if($justSaved)
                    ✓ Alterações Salvas com Sucesso!
                @else
                    {{ $editingId ? 'Salvar Alterações' : 'Confirmar Transação' }}
                @endif
            </span>
            <span wire:loading wire:target="save">
                Salvando alterações...
            </span>
        </button>
    </div>
</div>

