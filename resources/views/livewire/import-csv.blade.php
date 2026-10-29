@php use App\Support\Money; @endphp
<div class="space-y-4">
    <h1 class="page-title">Importar extrato (CSV)</h1>

    @if (! $rows)
        <form wire:submit="preview" class="card space-y-3">
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="label">Modelo do banco</label>
                    <select wire:model="bank_profile_id" class="input">
                        @foreach ($profiles as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Conta de destino</label>
                    <select wire:model="account_id" class="input">
                        @foreach ($accounts as $a)
                            <option value="{{ $a->id }}">{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="label">Arquivo CSV</label>
                <input type="file" wire:model="file" accept=".csv,text/csv,text/plain" class="input">
                <div wire:loading wire:target="file" class="mt-1 text-xs text-slate-500">Enviando arquivo...</div>
                @error('file') <p class="error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary w-full md:w-auto" wire:loading.attr="disabled">Pré-visualizar</button>
        </form>
    @else
        <div class="card flex flex-wrap items-center justify-between gap-3">
            <div class="text-sm">
                <div class="font-semibold">{{ $filename }}</div>
                <div class="text-slate-500">
                    {{ count($rows) }} linha(s) · <span class="text-emerald-600">{{ $newCount }} nova(s)</span> ·
                    {{ collect($rows)->where('duplicate', true)->count() }} já importada(s) · total <span class="{{ $newTotal >= 0 ? 'text-income' : 'text-expense' }}">{{ Money::format($newTotal) }}</span>
                </div>
            </div>
            <div class="flex gap-2">
                <button wire:click="cancel" class="btn-secondary">Cancelar</button>
                <button wire:click="confirm" class="btn-primary" wire:loading.attr="disabled" @disabled($newCount === 0)>Importar {{ $newCount }}</button>
            </div>
        </div>

        <div class="card !p-0 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs text-slate-500 dark:bg-slate-800">
                    <tr>
                        <th class="p-2">✔</th>
                        <th class="p-2">Data</th>
                        <th class="p-2">Descrição</th>
                        <th class="p-2 text-right">Valor</th>
                        <th class="p-2">Categoria</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($rows as $i => $row)
                        <tr wire:key="row-{{ $i }}" @class(['opacity-40' => $row['duplicate'] || $row['skip']])>
                            <td class="p-2">
                                @if ($row['duplicate'])
                                    <span title="Já importado">♻️</span>
                                @else
                                    <input type="checkbox" @checked(! $row['skip'])
                                           wire:click="$set('rows.{{ $i }}.skip', {{ $row['skip'] ? 'false' : 'true' }})">
                                @endif
                            </td>
                            <td class="p-2 whitespace-nowrap">{{ \Carbon\Carbon::parse($row['date'])->format('d/m/y') }}</td>
                            <td class="p-2">{{ $row['description'] }}</td>
                            <td @class(['p-2 text-right whitespace-nowrap font-medium', 'text-expense' => $row['amount_cents'] < 0, 'text-income' => $row['amount_cents'] >= 0])>
                                {{ Money::format($row['amount_cents']) }}
                            </td>
                            <td class="p-2">
                                <select wire:model="rows.{{ $i }}.category_id" class="input !py-1 min-w-36" @disabled($row['duplicate'])>
                                    <option value="">—</option>
                                    @foreach ($categories as $c)
                                        <option value="{{ $c->id }}">{{ $c->icon }} {{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card">
        <h2 class="mb-2 font-semibold">Importações recentes</h2>
        @forelse ($batches as $b)
            <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0 dark:border-slate-800">
                <div>
                    <div>{{ $b->filename ?? strtoupper($b->source) }}</div>
                    <div class="text-xs text-slate-400">{{ $b->created_at->format('d/m/Y H:i') }} · {{ $b->account?->name }} · {{ $b->rows_imported }} novo(s), {{ $b->rows_skipped }} ignorado(s)</div>
                </div>
                <button wire:click="undo('{{ $b->id }}')" wire:confirm="Desfazer esta importação? Os lançamentos dela serão excluídos." class="text-xs text-rose-600">Desfazer</button>
            </div>
        @empty
            <p class="text-sm text-slate-500">Nenhuma importação ainda.</p>
        @endforelse
    </div>
</div>

