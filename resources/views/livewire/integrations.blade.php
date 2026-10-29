<div class="space-y-6 animate-page-enter">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-[0.6875rem] uppercase tracking-[0.08em] text-on-surface-variant font-semibold">Conexões Bancárias</p>
            <h1 class="text-headline-md font-extrabold text-on-surface tracking-tight">Open Finance &amp; APIs</h1>
        </div>
        <div class="flex items-center gap-2">
            <label class="text-xs text-on-surface-variant font-semibold">Período (dias):</label>
            <input type="number" min="1" max="365" wire:model="days" class="input !w-20 !py-1.5 text-center font-bold tabular-nums">
        </div>
    </div>

    @if ($successMessage)
        <div class="bg-primary/15 border border-primary/30 rounded-2xl p-4 flex items-center gap-3 text-xs font-bold text-primary animate-pop">
            <span class="material-symbols-outlined text-lg">check_circle</span>
            <span>{{ $successMessage }}</span>
        </div>
    @endif

    @if ($errorMessage)
        <div class="bg-error/15 border border-error/30 rounded-2xl p-4 flex items-center gap-3 text-xs font-bold text-error animate-pop">
            <span class="material-symbols-outlined text-lg">error</span>
            <span>{{ $errorMessage }}</span>
        </div>
    @endif

    <!-- 1. Mercado Pago API -->
    <section class="bg-surface-container-low rounded-2xl p-5 ghost-border space-y-4 animate-stagger-1">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-[#00b1ea]/15 border border-[#00b1ea]/35 text-[#38bdf8] flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">handshake</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-base font-bold text-on-surface">Integração Mercado Pago</h2>
                        <span class="rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold {{ $configured ? 'bg-primary/15 text-primary' : 'bg-secondary-container/25 text-secondary-fixed-dim' }}">
                            {{ $configured ? 'Configurado no .env' : 'Pendente' }}
                        </span>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        Importa Pix recebidos (identificando <strong>Própria Titularidade</strong> e <strong>Banco/Agência/Conta</strong>) e compras aprovadas para a conta <strong>"{{ $accountName }}"</strong>.
                    </p>
                </div>
            </div>
        </div>

        @if ($accountInfo)
            <div class="rounded-xl bg-primary/10 border border-primary/25 p-3 text-xs text-primary">
                Conectado como <strong>{{ $accountInfo['nickname'] ?? $accountInfo['email'] }}</strong> (ID {{ $accountInfo['id'] }}).
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button type="button" wire:click="test" class="btn-secondary tap-bounce" @disabled(! $configured)>
                <span class="material-symbols-outlined text-base">verified</span>
                <span>Testar Conexão</span>
            </button>
            <button type="button" wire:click="import" wire:loading.attr="disabled" class="btn-primary tap-bounce" @disabled(! $configured)>
                <span class="material-symbols-outlined text-base">download</span>
                <span>Importar / Atualizar Descrições em "{{ $accountName }}"</span>
            </button>
        </div>
    </section>

    <!-- 2. Dicionário de Contas Conhecidas (Agência + Conta -> Nome do Titular) -->
    <section class="bg-surface-container-low rounded-2xl p-5 ghost-border space-y-4 animate-stagger-2">
        <div class="flex items-start justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-primary/15 border border-primary/30 text-primary flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">contact_page</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-base font-bold text-on-surface">Dicionário de Contas Conhecidas (Pix)</h2>
                        <span class="rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold bg-surface-container-highest text-on-surface-variant">
                            {{ count($knownAccounts) }} contas mapeadas
                        </span>
                        <span class="rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold bg-primary/15 text-primary flex items-center gap-1">
                            <span class="material-symbols-outlined text-[13px]">cloud_done</span>
                            Salvamento automático ao digitar
                        </span>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        Digite o nome da pessoa ou empresa em qualquer campo abaixo: ele é <strong>salvo automaticamente na hora</strong> e já atualiza os lançamentos dessa conta.
                    </p>
                </div>
            </div>
        </div>

        <div class="space-y-2.5 pt-1">
            @foreach ($knownAccounts as $key => $acc)
                <div wire:key="dict-row-{{ $key }}" class="bg-surface-container rounded-xl p-3.5 ghost-border grid grid-cols-1 md:grid-cols-12 gap-3 items-center">
                    <div class="md:col-span-5 flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl {{ !empty($acc['is_same_owner']) ? 'bg-primary/15 text-primary' : 'bg-surface-container-highest text-on-surface-variant' }} flex items-center justify-center shrink-0">
                            <span class="material-symbols-outlined text-lg">{{ !empty($acc['is_same_owner']) ? 'person_check' : 'account_balance' }}</span>
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-extrabold text-on-surface">{{ $acc['bank_name'] }}</span>
                                <span class="text-[0.6875rem] font-mono px-2 py-0.5 rounded bg-surface-container-highest text-primary font-semibold">
                                    Ag. {{ $acc['branch'] }} · C. {{ $acc['account_id'] }}
                                </span>
                                @if (!empty($acc['is_same_owner']))
                                    <span class="text-[0.625rem] font-bold px-2 py-0.5 rounded-full bg-primary/20 text-primary">
                                        Própria Titularidade
                                    </span>
                                @endif
                            </div>
                            @if (!empty($acc['sample_hint']))
                                <p class="text-[0.6875rem] text-on-surface-variant truncate mt-0.5">
                                    Histórico: {{ $acc['sample_hint'] }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="md:col-span-6 relative">
                        <input
                            type="text"
                            value="{{ $acc['label'] ?? '' }}"
                            data-account-key="{{ $key }}"
                            oninput="window.queueSaveKnownAccount(this, '{{ $key }}')"
                            onchange="window.saveKnownAccountNow(this, '{{ $key }}')"
                            placeholder="{{ !empty($acc['is_same_owner']) ? 'Própria Titularidade (Guilherme)' : 'Digite o nome da pessoa ou empresa desta conta...' }}"
                            class="input !py-2 !pr-24 text-xs font-semibold"
                        />
                        <span
                            id="save-badge-{{ $key }}"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[0.65rem] font-bold text-primary items-center gap-0.5 pointer-events-none {{ !empty($acc['label']) ? 'flex' : 'hidden' }}"
                        >
                            <span class="material-symbols-outlined text-sm" id="save-icon-{{ $key }}">check_circle</span>
                            <span id="save-text-{{ $key }}">Salvo</span>
                        </span>
                    </div>

                    <div class="md:col-span-1 flex justify-end">
                        <button
                            type="button"
                            wire:click="removeDictionaryAccount('{{ $key }}')"
                            class="p-2 rounded-lg text-on-surface-variant hover:text-error hover:bg-surface-container-high transition-colors"
                            title="Remover conta do dicionário"
                        >
                            <span class="material-symbols-outlined text-base">delete</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Adicionar nova conta manualmente -->
        <div class="pt-3 border-t border-outline-variant/15">
            <p class="text-[0.6875rem] font-bold uppercase tracking-wider text-on-surface-variant mb-2">Adicionar Outra Conta Manualmente</p>
            <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5 items-center">
                <div class="sm:col-span-3">
                    <input type="text" wire:model="newBankName" placeholder="Banco (ex: Itaú, Nubank)" class="input !py-2 text-xs" />
                </div>
                <div class="sm:col-span-2">
                    <input type="text" wire:model="newBranch" placeholder="Agência (opcional)" class="input !py-2 text-xs font-mono" />
                </div>
                <div class="sm:col-span-3">
                    <input type="text" wire:model="newAccountId" placeholder="Conta / ID Operação (#)" class="input !py-2 text-xs font-mono" />
                </div>
                <div class="sm:col-span-3">
                    <input type="text" wire:model="newLabel" placeholder="Nome do Titular" class="input !py-2 text-xs" />
                </div>
                <div class="sm:col-span-1">
                    <button type="button" wire:click="addDictionaryAccount" class="btn-secondary !w-full !py-2 text-xs tap-bounce" title="Adicionar">
                        <span class="material-symbols-outlined text-base">add</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Open Finance Brasil (Nubank / Pluggy) -->
    <section class="bg-surface-container-low rounded-2xl p-5 ghost-border space-y-4 animate-stagger-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-2xl bg-[#8A05BE]/20 border border-[#8A05BE]/40 text-[#c084fc] flex items-center justify-center shrink-0">
                    <span class="material-symbols-outlined text-2xl">account_balance</span>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="text-base font-bold text-on-surface">Nubank &amp; Bancos via Open Finance (Pluggy)</h2>
                        <span class="rounded-full px-2.5 py-0.5 text-[0.65rem] font-bold {{ $pluggyConfigured ? 'bg-primary/15 text-primary' : 'bg-error/15 text-error' }}">
                            {{ $pluggyConfigured ? 'Credenciais Ativas' : 'Pendente no .env' }}
                        </span>
                    </div>
                    <p class="text-xs text-on-surface-variant mt-0.5">
                        Para uso pessoal gratuito, conecte sua conta em <a href="https://meu.pluggy.ai" target="_blank" class="text-primary underline">meu.pluggy.ai</a> e cole o <strong>Item ID</strong> abaixo.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 pt-1">
            <button
                type="button"
                wire:click="startPluggyConnect"
                wire:loading.attr="disabled"
                @disabled(! $pluggyConfigured)
                class="btn-secondary tap-bounce"
            >
                <span class="material-symbols-outlined text-lg">qr_code_scanner</span>
                <span>Abrir Widget Pluggy Connect</span>
            </button>

            <div class="flex items-center gap-2 ml-auto">
                <input
                    type="text"
                    wire:model="manualItemId"
                    placeholder="Cole o Item ID do Meu Pluggy..."
                    class="input !py-2 !w-60 text-xs font-mono"
                />
                <button type="button" wire:click="addManualPluggyItem" class="btn-primary !py-2 text-xs tap-bounce">
                    Vincular ID
                </button>
            </div>
        </div>

        @if (count($pluggyItems) > 0)
            <div class="pt-3 border-t border-outline-variant/15 space-y-2.5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Contas Conectadas via Open Finance</h3>
                @foreach ($pluggyItems as $item)
                    <div class="bg-surface-container rounded-xl p-3.5 ghost-border flex items-center justify-between gap-3 flex-wrap">
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse"></span>
                            <div>
                                <p class="text-sm font-bold text-on-surface">{{ $item['connector_name'] ?? 'Nubank' }}</p>
                                <p class="text-[0.6875rem] text-on-surface-variant font-mono">
                                    Item: {{ $item['id'] }} · Última sync: {{ $item['last_sync_at'] ?? 'Agora' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                wire:click="syncPluggyItem('{{ $item['id'] }}')"
                                wire:loading.attr="disabled"
                                class="px-3.5 py-2 rounded-xl bg-primary/15 hover:bg-primary/25 text-primary text-xs font-bold flex items-center gap-1.5 tap-bounce"
                            >
                                <span class="material-symbols-outlined text-base">sync</span>
                                <span>Sincronizar Agora</span>
                            </button>
                            <button
                                type="button"
                                wire:click="removePluggyItem('{{ $item['id'] }}')"
                                class="p-2 rounded-xl bg-surface-container-high text-on-surface-variant hover:text-error transition-colors"
                                title="Desvincular"
                            >
                                <span class="material-symbols-outlined text-base">delete</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- 4. Histórico de Sincronizações -->
    <section class="bg-surface-container-low rounded-2xl p-5 ghost-border space-y-3 animate-stagger-4">
        <h2 class="text-sm font-bold text-on-surface">Últimas Sincronizações Automáticas</h2>
        @forelse ($batches as $b)
            <div class="flex items-center justify-between border-b border-outline-variant/15 py-2.5 text-xs last:border-0">
                <div class="flex items-center gap-2.5">
                    <span class="px-2 py-0.5 rounded bg-surface-container-high font-bold text-primary uppercase text-[0.625rem]">
                        {{ $b->source }}
                    </span>
                    <span class="text-on-surface font-medium">{{ $b->filename }}</span>
                </div>
                <div class="text-on-surface-variant tabular-nums">
                    {{ $b->created_at->format('d/m/Y H:i') }} · <strong class="text-primary">{{ $b->rows_imported }} novo(s)</strong>, {{ $b->rows_skipped }} ignorado(s)
                </div>
            </div>
        @empty
            <p class="text-xs text-on-surface-variant">Nenhuma sincronização realizada ainda.</p>
        @endforelse
    </section>

    <!-- Auto-Save Dicionário de Contas & Pluggy Connect SDK -->
    <script src="https://cdn.pluggy.ai/pluggy-connect/v2.8.2/pluggy-connect.js"></script>
    <script>
        window._knownAccountTimers = window._knownAccountTimers || {};

        window.setKnownAccountBadge = function (key, state) {
            const badge = document.getElementById('save-badge-' + key);
            const icon = document.getElementById('save-icon-' + key);
            const text = document.getElementById('save-text-' + key);
            if (!badge || !icon || !text) return;

            badge.classList.remove('hidden');
            badge.classList.add('flex');

            if (state === 'saving') {
                icon.textContent = 'sync';
                text.textContent = 'Salvando...';
            } else if (state === 'saved') {
                icon.textContent = 'check_circle';
                text.textContent = 'Salvo';
            } else if (state === 'error') {
                icon.textContent = 'error';
                text.textContent = 'Erro';
            }
        };

        window.saveKnownAccountNow = function (inputEl, key) {
            if (window._knownAccountTimers[key]) {
                clearTimeout(window._knownAccountTimers[key]);
                delete window._knownAccountTimers[key];
            }

            const label = inputEl.value;
            window.setKnownAccountBadge(key, 'saving');

            fetch('{{ route('integrations.dictionary.save') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ key: key, label: label })
            })
            .then(r => r.json())
            .then(data => {
                if (data && data.ok) {
                    window.setKnownAccountBadge(key, 'saved');
                    if (window.emeraldAudio) window.emeraldAudio.playSave();
                } else {
                    window.setKnownAccountBadge(key, 'error');
                }
            })
            .catch(() => {
                window.setKnownAccountBadge(key, 'error');
            });
        };

        window.queueSaveKnownAccount = function (inputEl, key) {
            window.setKnownAccountBadge(key, 'saving');
            if (window._knownAccountTimers[key]) {
                clearTimeout(window._knownAccountTimers[key]);
            }
            window._knownAccountTimers[key] = setTimeout(() => {
                window.saveKnownAccountNow(inputEl, key);
            }, 300);
        };

        document.addEventListener('livewire:init', () => {
            Livewire.on('open-pluggy-connect', ({ token }) => {
                if (typeof PluggyConnect === 'undefined') {
                    alert('Não foi possível carregar o SDK do Pluggy Connect. Verifique sua conexão com a internet.');
                    return;
                }

                const pluggyConnect = new PluggyConnect({
                    connectToken: token,
                    includeSandbox: false,
                    onSuccess: (itemData) => {
                        if (itemData && itemData.item && itemData.item.id) {
                            @this.call('onPluggyItemConnected', itemData.item.id);
                        }
                    },
                    onError: (error) => {
                        console.error('Pluggy Connect error:', error);
                    },
                });

                pluggyConnect.init();
            });
        });
    </script>
</div>
