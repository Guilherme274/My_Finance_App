<!DOCTYPE html>
<html lang="pt-BR" class="dark h-full">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover"/>
    <meta name="theme-color" content="#0f131c"/>
    <title>{{ isset($title) ? $title.' · ' : '' }}Emerald · Gestão Financeira</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body style="--color-primary: {{ in_array(\App\Models\Setting::get('settings_accent', '#4edea3'), ['#4edea3', '#3b82f6', '#8b5cf6', '#f59e0b'], true) ? \App\Models\Setting::get('settings_accent', '#4edea3') : '#4edea3' }}" class="bg-surface font-body-md text-body-md text-on-surface antialiased flex flex-col min-h-screen selection:bg-primary/30 selection:text-primary">
@php
    $primaryNav = [
        ['route' => 'dashboard', 'label' => 'Início', 'icon' => 'dashboard'],
        ['route' => 'calendar', 'label' => 'Agenda', 'icon' => 'calendar_today'],
        ['route' => 'budget', 'label' => 'Orçamento', 'icon' => 'pie_chart'],
        ['route' => 'investments', 'label' => 'Investir', 'icon' => 'trending_up'],
    ];

    $secondaryNav = [
        ['route' => 'transactions', 'label' => 'Extrato Completo', 'icon' => 'receipt_long'],
        ['route' => 'import', 'label' => 'Importar CSV', 'icon' => 'upload_file'],
        ['route' => 'accounts', 'label' => 'Contas & Cartões', 'icon' => 'account_balance_wallet'],
        ['route' => 'categories', 'label' => 'Categorias & Regras', 'icon' => 'category'],
        ['route' => 'sync', 'label' => 'Sincronizar & Backup', 'icon' => 'sync'],
    ];
    if (config('finance.role') === 'hub') {
        $secondaryNav[] = ['route' => 'integrations', 'label' => 'API Mercado Pago', 'icon' => 'handshake'];
    }

    $isStackPage = request()->routeIs('transactions.create') || request()->routeIs('transactions');
    $headerSupertitle = request()->routeIs('transactions') ? 'Transações' : 'Emerald';
@endphp

<div class="flex min-h-screen w-full">
    {{-- Sidebar Desktop --}}
    <aside class="hidden lg:flex w-64 shrink-0 flex-col bg-surface-container-lowest border-r border-outline-variant/25 p-5 sticky top-0 h-screen">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 mb-7 px-2 group">
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-primary-container via-primary to-secondary text-on-primary flex items-center justify-center shadow-[0_0_20px_rgba(78,222,163,0.35)] group-hover:scale-105 transition-transform">
                <span class="material-symbols-outlined text-[20px] font-bold">diamond</span>
            </div>
            <div class="flex flex-col">
                <span class="text-label-sm text-primary tracking-wider uppercase font-semibold">Emerald</span>
                <span class="text-title-md text-on-surface leading-tight">Wealth Terminal</span>
            </div>
        </a>

        <a href="{{ route('transactions.create') }}" wire:navigate
           class="mb-5 w-full py-2.5 px-4 rounded-xl bg-primary text-on-primary font-semibold text-sm flex items-center justify-center gap-2 shadow-lg shadow-primary/20 hover:brightness-105 active:scale-95 transition-all">
            <span class="material-symbols-outlined text-[20px]">add_circle</span>
            <span>Nova Transação</span>
        </a>

        <div class="text-label-sm uppercase tracking-wider text-on-surface-variant/70 px-3 mb-2">Visão Principal</div>
        <nav class="flex flex-col gap-1 mb-6">
            @foreach ($primaryNav as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}" wire:navigate
                   @class([
                       'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 tap-bounce',
                       'bg-primary/15 text-primary shadow-[inset_0_0_0_1px_rgba(78,222,163,0.25)] font-semibold' => $active,
                       'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' => ! $active,
                   ])>
                    <span class="material-symbols-outlined text-[20px]">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="text-label-sm uppercase tracking-wider text-on-surface-variant/70 px-3 mb-2">Gestão & Dados</div>
        <nav class="flex flex-1 flex-col gap-1">
            @foreach ($secondaryNav as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}" wire:navigate
                   @class([
                       'flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-200 tap-bounce',
                       'bg-primary/15 text-primary shadow-[inset_0_0_0_1px_rgba(78,222,163,0.25)] font-semibold' => $active,
                       'text-on-surface-variant hover:bg-surface-container hover:text-on-surface' => ! $active,
                   ])>
                    <span class="material-symbols-outlined text-[19px]">{{ $item['icon'] }}</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </nav>

        <div class="mt-4 pt-3 border-t border-outline-variant/20 px-2 flex items-center justify-between text-xs text-on-surface-variant">
            <span class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
                {{ config('finance.role') === 'hub' ? 'Hub PC (MySQL)' : 'Android Offline' }}
            </span>
            <a href="{{ route('sync') }}" wire:navigate class="text-primary hover:underline">Sync</a>
        </div>
    </aside>

    {{-- Área Principal --}}
    <div class="flex-1 flex flex-col min-w-0">
        <div x-data="{ drawerOpen: false }">
        {{-- Top Header Mobile / Tablet --}}
        <header class="fixed top-0 left-0 right-0 lg:left-64 z-40 bg-surface/85 backdrop-blur-xl pt-safe shadow-[0_1px_12px_rgba(0,0,0,0.35)] border-b border-outline-variant/15">
            <div class="h-16 px-gutter max-w-4xl mx-auto flex items-center justify-between">
                <div class="flex items-center gap-space-sm">
                    @if ($isStackPage)
                        <a href="{{ route('dashboard') }}" wire:navigate aria-label="Voltar"
                           class="w-10 h-10 flex items-center justify-center rounded-full text-on-surface hover:text-primary hover:bg-surface-container-high transition-all tap-bounce">
                            <span class="material-symbols-outlined text-[22px]">arrow_back</span>
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" wire:navigate
                           class="w-9 h-9 rounded-xl bg-gradient-to-tr from-primary-container/25 via-primary/20 to-secondary/25 border border-primary/30 flex items-center justify-center text-primary shadow-[0_0_15px_rgba(78,222,163,0.2)] tap-bounce">
                            <span class="material-symbols-outlined text-[20px]">diamond</span>
                        </a>
                    @endif
                    <div class="flex flex-col">
                        <span class="text-label-sm text-primary tracking-wider uppercase font-semibold">{{ $headerSupertitle }}</span>
                        <span class="text-title-md text-on-surface leading-tight">{{ $title ?? 'Início' }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-1">
                    <a href="{{ route('import') }}" wire:navigate title="Importar CSV"
                       class="w-10 h-10 flex items-center justify-center rounded-full text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors tap-bounce">
                        <span class="material-symbols-outlined text-[21px]">upload_file</span>
                    </a>
                    <a href="{{ route('sync') }}" wire:navigate title="Sincronizar"
                       class="w-10 h-10 flex items-center justify-center rounded-full text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors relative tap-bounce">
                        <span class="material-symbols-outlined text-[21px]">sync</span>
                        <span class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-primary ring-2 ring-surface"></span>
                    </a>
                    <button type="button" x-on:click="drawerOpen = !drawerOpen" aria-label="Menu"
                            class="w-10 h-10 flex items-center justify-center rounded-full text-on-surface-variant hover:text-primary hover:bg-surface-container-high transition-colors tap-bounce">
                        <span class="material-symbols-outlined text-[22px]" x-text="drawerOpen ? 'close' : 'apps'">apps</span>
                    </button>
                    <a href="{{ route('settings') }}" wire:navigate title="Configurações" aria-label="Configurações"
                       @class([
                           'w-10 h-10 flex items-center justify-center rounded-full transition-colors tap-bounce',
                           'text-primary bg-primary/10' => request()->routeIs('settings'),
                           'text-on-surface-variant hover:text-primary hover:bg-surface-container-high' => ! request()->routeIs('settings'),
                       ])>
                        <span class="material-symbols-outlined text-[22px]">account_circle</span>
                    </a>
                </div>
            </div>
        </header>

        {{-- Menu Rápido Deslizante (acesso a Contas, Categorias, Extrato, Importar, Sync, Mercado Pago) --}}
        <div x-show="drawerOpen" x-transition.opacity style="display: none;"
             class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm" x-on:click.self="drawerOpen = false">
            <div x-show="drawerOpen"
                 x-transition:enter="transition ease-out duration-250"
                 x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
                 class="mx-auto mt-20 max-w-md rounded-2xl bg-surface-container-high border border-primary/20 p-4 shadow-2xl">
                <div class="flex items-center justify-between mb-3 px-1">
                    <span class="text-label-sm uppercase tracking-wider text-primary font-semibold">Central de Módulos</span>
                    <button type="button" x-on:click="drawerOpen = false" class="text-on-surface-variant hover:text-on-surface">
                        <span class="material-symbols-outlined text-[20px]">close</span>
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (array_merge($primaryNav, $secondaryNav) as $item)
                        <a href="{{ route($item['route']) }}" wire:navigate x-on:click="drawerOpen = false"
                           @class([
                               'flex items-center gap-2.5 p-3 rounded-xl text-sm transition-all tap-bounce',
                               'bg-primary/15 text-primary font-semibold border border-primary/30' => request()->routeIs($item['route']),
                               'bg-surface-container text-on-surface hover:bg-surface-bright' => ! request()->routeIs($item['route']),
                           ])>
                            <span class="material-symbols-outlined text-[20px] text-primary">{{ $item['icon'] }}</span>
                            <span class="truncate">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
        </div>

        {{-- Conteúdo da Página com Animação de Entrada --}}
        <main class="flex-1 flex flex-col relative w-full pt-20 pb-28 lg:pb-10 bg-surface px-margin max-w-4xl mx-auto">
            @if (session('status'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 -translate-y-3"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0 -translate-y-3"
                     class="mb-4 flex items-center gap-3 p-3.5 rounded-xl bg-surface-container-highest border border-primary/30 text-on-surface shadow-[0_0_24px_-6px_rgba(78,222,163,0.35)]">
                    <div class="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary shrink-0">
                        <span class="material-symbols-outlined text-[20px]">done_all</span>
                    </div>
                    <span class="text-sm font-medium flex-1">{{ session('status') }}</span>
                    <button type="button" x-on:click="show = false" class="text-on-surface-variant hover:text-on-surface">
                        <span class="material-symbols-outlined text-[18px]">close</span>
                    </button>
                </div>
            @endif

            <div class="animate-page-enter w-full">
                {{ $slot }}
            </div>
        </main>
    </div>
</div>

{{-- Toast Flutuante Global (sempre visível em qualquer posição de rolagem) --}}
<div id="emerald-floating-toast"
     class="fixed top-20 left-1/2 -translate-x-1/2 z-[100] pointer-events-none transition-all duration-300 opacity-0 -translate-y-4 scale-95">
    <div id="emerald-floating-toast-box"
         class="pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-2xl bg-surface-container-highest/95 backdrop-blur-xl border border-primary/40 text-on-surface shadow-[0_12px_36px_rgba(16,185,129,0.35)]">
        <div id="emerald-floating-toast-icon-wrap" class="w-8 h-8 rounded-xl bg-primary text-on-primary flex items-center justify-center shrink-0">
            <span id="emerald-floating-toast-icon" class="material-symbols-outlined text-[20px]">check_circle</span>
        </div>
        <div class="pr-2">
            <p id="emerald-floating-toast-title" class="text-xs font-extrabold text-primary uppercase tracking-wider">Alteração Salva</p>
            <p id="emerald-floating-toast-msg" class="text-sm font-bold text-on-surface">Suas alterações foram salvas com sucesso!</p>
        </div>
    </div>
</div>

<script data-navigate-once>
    (function () {
        function buildWavUri(notes, stepSec, durationSec, volume) {
            const sampleRate = 44100;
            const numSamples = Math.floor(sampleRate * durationSec);
            const buffer = new ArrayBuffer(44 + numSamples * 2);
            const view = new DataView(buffer);
            const writeStr = (off, str) => {
                for (let i = 0; i < str.length; i++) view.setUint8(off + i, str.charCodeAt(i));
            };
            writeStr(0, 'RIFF');
            view.setUint32(4, 36 + numSamples * 2, true);
            writeStr(8, 'WAVE');
            writeStr(12, 'fmt ');
            view.setUint32(16, 16, true);
            view.setUint16(20, 1, true);
            view.setUint16(22, 1, true);
            view.setUint32(24, sampleRate, true);
            view.setUint32(28, sampleRate * 2, true);
            view.setUint16(32, 2, true);
            view.setUint16(34, 16, true);
            writeStr(36, 'data');
            view.setUint32(40, numSamples * 2, true);

            for (let i = 0; i < numSamples; i++) {
                const t = i / sampleRate;
                let sample = 0;
                for (let n = 0; n < notes.length; n++) {
                    const start = n * stepSec;
                    if (t >= start) {
                        const lt = t - start;
                        const env = Math.min(1, lt / 0.01) * Math.exp(-lt * 7.0);
                        const f = notes[n];
                        sample += (Math.sin(2 * Math.PI * f * lt) + 0.3 * Math.sin(4 * Math.PI * f * lt)) * env;
                    }
                }
                sample = Math.max(-1, Math.min(1, (sample / notes.length) * volume * 2.4));
                view.setInt16(44 + i * 2, sample * 32767, true);
            }
            const bytes = new Uint8Array(buffer);
            let bin = '';
            for (let i = 0; i < bytes.byteLength; i++) bin += String.fromCharCode(bytes[i]);
            return 'data:audio/wav;base64,' + btoa(bin);
        }

        const saveWav = buildWavUri([587.33, 739.99, 880.00, 1174.66], 0.055, 0.55, 0.85);
        const errWav = buildWavUri([240.00, 180.00], 0.11, 0.38, 0.75);

        window.emeraldAudio = window.emeraldAudio || {
            lastPlayedAt: 0,
            playSave() {
                const now = Date.now();
                if (now - this.lastPlayedAt < 800) return;
                this.lastPlayedAt = now;
                try {
                    const a = new Audio(saveWav);
                    a.volume = 0.95;
                    a.play().catch(() => {});
                } catch (e) {}
            },
            playError() {
                const now = Date.now();
                if (now - this.lastPlayedAt < 500) return;
                this.lastPlayedAt = now;
                try {
                    const a = new Audio(errWav);
                    a.volume = 0.9;
                    a.play().catch(() => {});
                } catch (e) {}
            }
        };
    })();

    window._emeraldToastTimer = null;
    window.showEmeraldToast = function (message, type = 'save') {
        const toast = document.getElementById('emerald-floating-toast');
        const msgEl = document.getElementById('emerald-floating-toast-msg');
        const titleEl = document.getElementById('emerald-floating-toast-title');
        const iconEl = document.getElementById('emerald-floating-toast-icon');
        if (!toast || !msgEl) return;

        msgEl.textContent = message || 'Alterações salvas com sucesso!';
        if (type === 'error') {
            titleEl.textContent = 'Atenção';
            iconEl.textContent = 'error';
        } else {
            titleEl.textContent = 'Salvo com Sucesso';
            iconEl.textContent = 'check_circle';
        }

        toast.classList.remove('opacity-0', '-translate-y-4', 'scale-95');
        toast.classList.add('opacity-100', 'translate-y-0', 'scale-100');

        if (window._emeraldToastTimer) clearTimeout(window._emeraldToastTimer);
        window._emeraldToastTimer = setTimeout(() => {
            toast.classList.remove('opacity-100', 'translate-y-0', 'scale-100');
            toast.classList.add('opacity-0', '-translate-y-4', 'scale-95');
        }, 3800);
    };

    window.addEventListener('emerald-feedback', (event) => {
        const detail = event.detail || {};
        const message = detail.message || (Array.isArray(detail) && detail[0]?.message) || 'Alterações salvas com sucesso!';
        const sound = detail.sound || (Array.isArray(detail) && detail[0]?.sound) || 'save';

        if (sound === 'error') {
            window.emeraldAudio.playError();
            window.showEmeraldToast(message, 'error');
        } else {
            window.emeraldAudio.playSave();
            window.showEmeraldToast(message, 'save');
        }
    });
</script>

<script data-navigate-once>
    (() => {
        let biometricsEnabled = @js(filter_var(\App\Models\Setting::get('settings_biometrics_enabled', false), FILTER_VALIDATE_BOOLEAN));
        let lockedInBackground = false;

        window.addEventListener('biometrics-enabled', () => {
            biometricsEnabled = true;
        });

        document.addEventListener('visibilitychange', async () => {
            if (!biometricsEnabled) {
                return;
            }

            if (document.hidden) {
                lockedInBackground = true;
                const body = new URLSearchParams({ _token: @js(csrf_token()) });
                try {
                    await fetch(@js(route('pin.lock')), {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body,
                        keepalive: true,
                    });
                } catch (_) {}
            } else if (lockedInBackground) {
                window.location.assign(@js(route('pin.show')));
            }
        });
    })();
</script>

{{-- Bottom Navigation Bar Mobile (fiel ao design Obsidian Emerald Wealth) --}}
@unless ($isStackPage)
<nav class="fixed bottom-0 left-0 right-0 z-40 pb-safe bg-surface-container-lowest/90 backdrop-blur-xl shadow-[0_-4px_24px_rgba(0,0,0,0.5)] border-t border-outline-variant/15 lg:hidden">
    <div class="relative flex items-center justify-around h-16 px-space-xs max-w-lg mx-auto">
        <a href="{{ route('dashboard') }}" wire:navigate
           @class([
               'flex flex-col items-center justify-center min-w-[56px] h-12 transition-all tap-bounce',
               'text-primary font-semibold scale-105' => request()->routeIs('dashboard'),
               'text-on-surface-variant hover:text-on-surface' => ! request()->routeIs('dashboard'),
           ])>
            <span class="material-symbols-outlined text-[22px]">dashboard</span>
            <span class="text-label-sm mt-0.5">Início</span>
        </a>

        <a href="{{ route('calendar') }}" wire:navigate
           @class([
               'flex flex-col items-center justify-center min-w-[56px] h-12 transition-all tap-bounce',
               'text-primary font-semibold scale-105' => request()->routeIs('calendar'),
               'text-on-surface-variant hover:text-on-surface' => ! request()->routeIs('calendar'),
           ])>
            <span class="material-symbols-outlined text-[22px]">calendar_today</span>
            <span class="text-label-sm mt-0.5">Agenda</span>
        </a>

        <div class="relative -top-5 flex items-center justify-center">
            <a href="{{ route('transactions.create') }}" wire:navigate aria-label="Adicionar Transação"
               class="w-14 h-14 rounded-full bg-primary text-on-primary flex items-center justify-center shadow-lg shadow-primary/25 hover:scale-105 active:scale-95 transition-transform">
                <span class="material-symbols-outlined text-[28px] font-bold">add</span>
            </a>
        </div>

        <a href="{{ route('budget') }}" wire:navigate
           @class([
               'flex flex-col items-center justify-center min-w-[56px] h-12 transition-all tap-bounce',
               'text-primary font-semibold scale-105' => request()->routeIs('budget'),
               'text-on-surface-variant hover:text-on-surface' => ! request()->routeIs('budget'),
           ])>
            <span class="material-symbols-outlined text-[22px]">pie_chart</span>
            <span class="text-label-sm mt-0.5">Orçamento</span>
        </a>

        <a href="{{ route('investments') }}" wire:navigate
           @class([
               'flex flex-col items-center justify-center min-w-[56px] h-12 transition-all tap-bounce',
               'text-primary font-semibold scale-105' => request()->routeIs('investments'),
               'text-on-surface-variant hover:text-on-surface' => ! request()->routeIs('investments'),
           ])>
            <span class="material-symbols-outlined text-[22px]">trending_up</span>
            <span class="text-label-sm mt-0.5">Investir</span>
        </a>
    </div>
</nav>
@endunless
@livewireScripts
</body>
</html>
