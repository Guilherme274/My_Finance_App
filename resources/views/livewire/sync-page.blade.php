<div class="space-y-4">
    <h1 class="page-title">Sincronização & Backup</h1>

    @if ($isHub)
        <div class="card space-y-3">
            <h2 class="font-semibold">🖥️ Este computador é o Hub de Sincronização</h2>
            <p class="text-sm text-slate-500">
                No aplicativo do celular, abra <strong>Sincronizar</strong> e preencha os endereços e o token abaixo.
                Coloque primeiro o endereço do <strong>Wi-Fi</strong> e na linha seguinte o do <strong>Tailscale</strong>: o celular tenta o Wi-Fi e, se estiver fora de casa, usa o Tailscale automaticamente.
            </p>

            <div>
                <label class="label">Endereços deste PC (copie para o celular)</label>
                <div class="rounded-lg bg-slate-100 p-3 font-mono text-xs dark:bg-slate-800">
                    @forelse ($addresses as $addr)
                        <div>http://{{ $addr['ip'] }}:{{ $port }} <span class="text-slate-400"># {{ $addr['type'] }}</span></div>
                    @empty
                        <div>Não foi possível detectar o IP automaticamente. Execute <code>ipconfig</code> no terminal.</div>
                    @endforelse
                </div>
            </div>

            <div>
                <label class="label">Token de sincronização</label>
                <div class="flex gap-2">
                    <input type="text" readonly value="{{ $hubToken }}" class="input font-mono text-xs">
                    @if (! config('finance.sync.token'))
                        <button wire:click="regenerateHubToken" wire:confirm="Gerar um novo token? O celular precisará ser atualizado." class="btn-secondary">Gerar outro</button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="card space-y-3">
        <h2 class="font-semibold">📱 Sincronizar com o PC</h2>
        @if ($lastSuccess)
            <div class="text-xs text-slate-500">Última sincronização: <strong>{{ $lastSuccess }}</strong> ({{ $lastUrl }})</div>
        @endif

        <div>
            <label class="label">Endereços do PC (um por linha: Wi-Fi primeiro, Tailscale depois)</label>
            <textarea wire:model="hubUrls" rows="2" placeholder="http://192.168.1.10:8000&#10;http://100.80.1.2:8000" class="input font-mono text-xs"></textarea>
        </div>
        <div>
            <label class="label">Token do PC</label>
            <input type="password" wire:model="token" class="input font-mono text-xs">
        </div>

        @if ($errorMessage)
            <div class="rounded-lg bg-rose-100 p-3 text-xs text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">{{ $errorMessage }}</div>
        @endif

        @if ($lastResult)
            <div class="rounded-lg bg-slate-100 p-3 text-xs dark:bg-slate-800">
                Conectado a <strong>{{ $lastResult['url'] }}</strong> ·
                Enviados: {{ ($lastResult['pushed']['inserted'] ?? 0) + ($lastResult['pushed']['updated'] ?? 0) }} ·
                Recebidos: {{ ($lastResult['pulled']['inserted'] ?? 0) + ($lastResult['pulled']['updated'] ?? 0) }}
            </div>
        @endif

        <div class="flex flex-wrap gap-2">
            <button wire:click="syncNow" wire:loading.attr="disabled" class="btn-primary">🔄 Sincronizar agora</button>
            <button wire:click="saveSettings" class="btn-secondary">Salvar configurações</button>
            <button wire:click="resetSyncCursors" class="btn-secondary text-xs">Forçar sync completo</button>
        </div>
    </div>

    <div class="card space-y-3">
        <h2 class="font-semibold">💾 Backup manual (JSON)</h2>
        <p class="text-xs text-slate-500">Funciona mesmo sem rede: exporte de um aparelho e importe no outro.</p>
        <div class="flex flex-wrap items-center gap-3">
            <button wire:click="exportBackup" class="btn-secondary">⬇️ Baixar backup</button>
            <form wire:submit="importBackup" class="flex flex-wrap items-center gap-2">
                <input type="file" wire:model="backupFile" accept=".json,application/json" class="input max-w-xs">
                <button type="submit" class="btn-secondary">⬆️ Restaurar</button>
            </form>
        </div>
        @error('backupFile') <p class="error">{{ $message }}</p> @enderror
    </div>
</div>

