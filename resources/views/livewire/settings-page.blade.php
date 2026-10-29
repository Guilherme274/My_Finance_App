<div class="w-full space-y-5 pb-10" x-on:settings-saved.window="window.showEmeraldToast($event.detail.message); if ($event.detail.accent) document.body.style.setProperty('--color-primary', $event.detail.accent)">
    <section class="rounded-2xl bg-surface-container-low p-4 shadow-md ghost-border">
        <div class="flex items-center gap-3">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-primary/15 text-primary ring-1 ring-primary/30">
                <span class="material-symbols-outlined text-[30px]">account_circle</span>
            </div>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-title-md font-semibold text-on-surface">Perfil local</h2>
                    <span class="rounded-full bg-primary/10 px-2 py-0.5 text-label-sm font-semibold uppercase tracking-wider text-primary">Emerald</span>
                </div>
                <p class="mt-0.5 text-body-sm text-on-surface-variant">Seus dados financeiros ficam neste dispositivo.</p>
                <div class="mt-1.5 flex items-center gap-1.5 text-label-sm text-primary">
                    <span class="material-symbols-outlined text-[15px]">verified_user</span>
                    <span>Armazenamento local protegido</span>
                </div>
            </div>
        </div>
    </section>

    <section class="space-y-4 rounded-2xl bg-surface-container-low p-4 shadow-md ghost-border">
        <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/15 text-primary"><span class="material-symbols-outlined text-[18px]">palette</span></div>
            <div>
                <h2 class="text-title-md font-semibold text-on-surface">Aparência & Identidade</h2>
                <p class="text-body-sm text-on-surface-variant">Ajuste a cor de destaque do aplicativo.</p>
            </div>
        </div>

        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <span class="text-label-md font-medium text-on-surface">Cor de destaque</span>
                <span class="text-body-sm text-primary">{{ match ($accent) { '#3b82f6' => 'Safira', '#8b5cf6' => 'Ametista', '#f59e0b' => 'Dourado', default => 'Esmeralda' } }}</span>
            </div>
            <div class="flex gap-3">
                @foreach (['#4edea3' => 'Esmeralda', '#3b82f6' => 'Safira', '#8b5cf6' => 'Ametista', '#f59e0b' => 'Dourado'] as $color => $name)
                    <button type="button" wire:click="chooseAccent('{{ $color }}')" aria-label="Usar cor {{ $name }}" @class([
                        'flex h-10 w-10 items-center justify-center rounded-full transition-transform active:scale-95',
                        'ring-2 ring-on-surface ring-offset-2 ring-offset-surface-container-low' => $accent === $color,
                    ]) style="background-color: {{ $color }}">
                        @if ($accent === $color)<span class="material-symbols-outlined text-[20px] text-surface-container-lowest">check</span>@endif
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="space-y-4 rounded-2xl bg-surface-container-low p-4 shadow-md ghost-border">
        <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/15 text-primary"><span class="material-symbols-outlined text-[18px]">account_balance_wallet</span></div>
            <div>
                <h2 class="text-title-md font-semibold text-on-surface">Preferências Financeiras</h2>
                <p class="text-body-sm text-on-surface-variant">Ciclo mensal e metodologia de orçamento.</p>
            </div>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-outline-variant/20 pt-4">
            <div>
                <div class="text-label-md font-medium text-on-surface">Exibir centavos</div>
                <div class="text-body-sm text-on-surface-variant">Ex.: R$ 14.520,80</div>
            </div>
            <button type="button" role="switch" aria-label="Exibir centavos" aria-checked="{{ $showCents ? 'true' : 'false' }}" wire:click="$set('showCents', {{ $showCents ? 'false' : 'true' }})" @class([
                'relative flex h-6 w-12 items-center rounded-full p-0.5 transition-colors',
                'bg-primary' => $showCents,
                'bg-surface-container-highest' => ! $showCents,
            ])>
                <span @class(['block h-5 w-5 rounded-full bg-on-primary shadow-sm transition-transform', 'translate-x-6' => $showCents])></span>
            </button>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-outline-variant/20 pt-4">
            <div>
                <div class="text-label-md font-medium text-on-surface">Modo de privacidade</div>
                <div class="text-body-sm text-on-surface-variant">Ocultar saldos quando estiver em público.</div>
            </div>
            <button type="button" role="switch" aria-label="Modo de privacidade" aria-checked="{{ $privacyMode ? 'true' : 'false' }}" wire:click="$set('privacyMode', {{ $privacyMode ? 'false' : 'true' }})" @class([
                'relative flex h-6 w-12 items-center rounded-full p-0.5 transition-colors',
                'bg-primary' => $privacyMode,
                'bg-surface-container-highest' => ! $privacyMode,
            ])>
                <span @class(['block h-5 w-5 rounded-full bg-on-primary shadow-sm transition-transform', 'translate-x-6' => $privacyMode])></span>
            </button>
        </div>

        <label class="flex items-center justify-between gap-3 border-t border-outline-variant/20 pt-4">
            <span>
                <span class="block text-label-md font-medium text-on-surface">Início do ciclo mensal</span>
                <span class="block text-body-sm text-on-surface-variant">Dia de referência do orçamento</span>
            </span>
            <select wire:model.live="monthlyCycleStart" class="rounded-lg bg-surface-container-high px-3 py-2 text-label-md font-semibold text-on-surface outline-none focus:ring-1 focus:ring-primary">
                @for ($day = 1; $day <= 28; $day++)
                    <option value="{{ $day }}">Dia {{ str_pad((string) $day, 2, '0', STR_PAD_LEFT) }}</option>
                @endfor
            </select>
        </label>

        <div class="space-y-2 border-t border-outline-variant/20 pt-4">
            <div class="flex items-center justify-between">
                <span class="text-label-md font-medium text-on-surface">Metodologia orçamentária</span>
                <span class="text-label-sm font-semibold text-primary">Ativa</span>
            </div>
            <div class="grid grid-cols-3 gap-2">
                @foreach (['50-30-20' => '50 / 30 / 20', '60-20-20' => '60 / 20 / 20', 'zero-based' => 'Base zero'] as $method => $label)
                    <button type="button" wire:click="$set('budgetMethod', '{{ $method }}')" @class([
                        'rounded-lg px-2 py-2.5 text-label-sm font-semibold transition-colors',
                        'bg-primary/15 text-primary ring-1 ring-primary/30' => $budgetMethod === $method,
                        'bg-surface-container text-on-surface-variant hover:bg-surface-container-high' => $budgetMethod !== $method,
                    ])>{{ $label }}</button>
                @endforeach
            </div>
        </div>
    </section>

    <section class="space-y-4 rounded-2xl bg-surface-container-low p-4 shadow-md ghost-border">
        <div class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/15 text-primary"><span class="material-symbols-outlined text-[18px]">security</span></div>
            <div>
                <h2 class="text-title-md font-semibold text-on-surface">Segurança & Conexões</h2>
                <p class="text-body-sm text-on-surface-variant">Proteção do aplicativo e contas conectadas.</p>
            </div>
        </div>

        <div class="space-y-3 rounded-xl bg-surface-container p-3">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant">
                    <span class="material-symbols-outlined">pin</span>
                </div>
                <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-label-md font-semibold text-on-surface">PIN de acesso</span>
                        @if ($hasPin)
                            <span class="rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">
                                {{ $currentPinSource === 'app' ? 'Configurado no app' : 'Definido no .env' }}
                            </span>
                        @else
                            <span class="rounded-full bg-surface-container-high px-2 py-0.5 text-[11px] font-semibold text-on-surface-variant">Desativado</span>
                        @endif
                    </div>
                    <p class="text-body-sm text-on-surface-variant">
                        Senha numérica (4 a 8 dígitos) para proteger a abertura do aplicativo e servir de alternativa à biometria.
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @if ($hasPin && ! $isEditingPin && $currentPinSource === 'app')
                        <button type="button" wire:click="removePin" wire:confirm="Deseja remover o PIN configurado no app?" class="rounded-full bg-error/15 px-3 py-1.5 text-label-sm font-semibold text-error hover:bg-error/25">
                            Remover
                        </button>
                    @endif
                    <button type="button" wire:click="toggleEditPin" class="rounded-full bg-surface-container-high px-3 py-1.5 text-label-sm font-semibold text-on-surface hover:text-primary">
                        {{ $isEditingPin ? 'Cancelar' : ($hasPin ? 'Alterar' : 'Definir PIN') }}
                    </button>
                </div>
            </div>

            @if ($isEditingPin)
                <form wire:submit="savePin" class="space-y-3 border-t border-outline-variant/20 pt-3">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-label-sm font-medium text-on-surface">Novo PIN (números)</label>
                            <input type="password" inputmode="numeric" wire:model="newPin" maxlength="8" placeholder="Ex: 31012026" class="mt-1 w-full rounded-lg bg-surface-container-high px-3 py-2 text-label-md font-semibold text-on-surface outline-none focus:ring-1 focus:ring-primary">
                            @error('newPin')<p class="mt-1 text-label-sm text-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-label-sm font-medium text-on-surface">Confirmar novo PIN</label>
                            <input type="password" inputmode="numeric" wire:model="newPinConfirmation" maxlength="8" placeholder="Repita o PIN" class="mt-1 w-full rounded-lg bg-surface-container-high px-3 py-2 text-label-md font-semibold text-on-surface outline-none focus:ring-1 focus:ring-primary">
                            @error('newPinConfirmation')<p class="mt-1 text-label-sm text-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" wire:click="toggleEditPin" class="rounded-lg bg-surface-container-high px-3 py-1.5 text-label-sm font-semibold text-on-surface-variant">Cancelar</button>
                        <button type="submit" class="rounded-lg bg-primary px-4 py-1.5 text-label-sm font-semibold text-surface-container-lowest">Salvar PIN</button>
                    </div>
                </form>
            @endif
        </div>

        <div class="flex items-start gap-3 rounded-xl bg-surface-container p-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-surface-container-high text-on-surface-variant"><span class="material-symbols-outlined">fingerprint</span></div>
            <div class="min-w-0 flex-1 space-y-1">
                <div class="text-label-md font-semibold text-on-surface">Digital / reconhecimento facial</div>
                <p class="text-body-sm text-on-surface-variant">Solicitar autenticação ao abrir o app ou voltar para ele. O PIN configurado continua disponível como alternativa.</p>
                @error('biometrics')<p class="text-body-sm text-error">{{ $message }}</p>@enderror
            </div>
            @if ($biometricsEnabled)
                <button type="button" wire:click="disableBiometrics" class="shrink-0 rounded-full bg-primary/15 px-3 py-1.5 text-label-sm font-semibold text-primary">Ativada</button>
            @else
                <button type="button" wire:click="enableBiometrics" wire:loading.attr="disabled" wire:target="enableBiometrics" class="shrink-0 rounded-full bg-surface-container-high px-3 py-1.5 text-label-sm font-semibold text-on-surface hover:text-primary disabled:opacity-50">Ativar</button>
            @endif
        </div>

        <a href="{{ route('integrations') }}" wire:navigate class="flex items-center gap-3 rounded-xl bg-surface-container p-3 transition-colors hover:bg-surface-container-high">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-container-high text-primary"><span class="material-symbols-outlined">account_balance</span></span>
            <span class="min-w-0 flex-1"><span class="block text-label-md font-semibold text-on-surface">Open Finance</span><span class="block text-body-sm text-on-surface-variant">Gerenciar conexões bancárias</span></span>
            <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
        </a>

        <a href="{{ route('sync') }}" wire:navigate class="flex items-center gap-3 rounded-xl bg-surface-container p-3 transition-colors hover:bg-surface-container-high">
            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-container-high text-primary"><span class="material-symbols-outlined">cloud_sync</span></span>
            <span class="min-w-0 flex-1"><span class="block text-label-md font-semibold text-on-surface">Backup & sincronização</span><span class="block text-body-sm text-on-surface-variant">Exportar backup e gerenciar dispositivos</span></span>
            <span class="material-symbols-outlined text-on-surface-variant">chevron_right</span>
        </a>
    </section>

    <p class="px-2 text-center text-label-sm text-on-surface-variant">Emerald · Gestão Financeira</p>
</div>
