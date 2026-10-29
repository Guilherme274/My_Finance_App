@php use App\Support\Money; @endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="page-title !mb-0">Calendário</h1>
        <div class="flex items-center gap-1 rounded-xl bg-slate-200 p-1 dark:bg-slate-800">
            @foreach (['month' => 'Mês', 'week' => 'Semana', 'day' => 'Dia'] as $key => $label)
                <button wire:click="setMode('{{ $key }}')" @class(['rounded-lg px-3 py-1.5 text-sm', 'bg-white font-semibold shadow-sm dark:bg-slate-700' => $mode === $key])>{{ $label }}</button>
            @endforeach
        </div>
    </div>

    <div class="card flex items-center justify-between gap-2 !py-3">
        <button wire:click="movePeriod(-1)" class="btn-secondary" aria-label="Período anterior">‹</button>
        <div class="text-center">
            <div class="font-semibold capitalize">{{ $mode === 'month' ? $monthLabel : $periodLabel }}</div>
            @if ($mode !== 'month')
                <button wire:click="setMode('month')" class="text-xs text-sky-600">Voltar ao mês</button>
            @endif
        </div>
        <button wire:click="movePeriod(1)" class="btn-secondary" aria-label="Próximo período">›</button>
    </div>

    @if ($mode === 'month')
        <div class="card !p-2 md:!p-4">
            <div class="grid grid-cols-7 text-center text-xs font-semibold text-slate-500">
                @foreach ($weekLabels as $label)<div class="py-2">{{ $label }}</div>@endforeach
            </div>
            <div class="grid grid-cols-7 gap-1">
                @foreach ($monthDays as $day)
                    @php
                        $dayKey = $day->toDateString();
                        $dayTransactions = $transactionsByDate->get($dayKey, collect());
                        $expenses = abs((int) $dayTransactions->where('amount_cents', '<', 0)->sum('amount_cents'));
                        $isCurrentMonth = $day->month === $selectedDate->month;
                    @endphp
                    <button wire:click="selectDate('{{ $dayKey }}')" @class([
                        'min-h-20 rounded-lg border p-1 text-left hover:border-sky-400 md:min-h-24 md:p-2',
                        'border-slate-100 bg-slate-50 dark:border-slate-800 dark:bg-slate-900' => $isCurrentMonth,
                        'border-transparent bg-white text-slate-400 dark:bg-slate-950' => ! $isCurrentMonth,
                        'ring-2 ring-sky-500' => $dayKey === now()->toDateString(),
                    ])>
                        <span class="text-xs font-semibold">{{ $day->day }}</span>
                        @if ($dayTransactions->isNotEmpty())
                            <span class="mt-1 block truncate text-[10px] text-slate-500 md:text-xs">{{ $dayTransactions->count() }} lançamento(s)</span>
                            @if ($expenses > 0)<span class="block truncate text-[10px] font-semibold text-expense md:text-xs">−{{ Money::format($expenses) }}</span>@endif
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
        <p class="text-center text-xs text-slate-500">Selecione um dia para ver os detalhes e adicionar um lançamento.</p>
    @else
        @php $daysToShow = $mode === 'week' ? $weekDays : collect([$selectedDate]); @endphp
        @foreach ($daysToShow as $day)
            @php $dayTransactions = $transactionsByDate->get($day->toDateString(), collect()); @endphp
            <section class="card !p-0">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                    <h2 class="font-semibold capitalize">{{ $day->locale('pt_BR')->translatedFormat('D, d \d\e F') }}</h2>
                    <a href="{{ route('transactions', ['novo' => 1, 'data' => $day->toDateString()]) }}" wire:navigate class="text-sm font-medium text-sky-600">+ Lançar</a>
                </div>
                @forelse ($dayTransactions as $transaction)
                    <a href="{{ route('transactions', ['month' => $day->format('Y-m'), 'editar' => $transaction->id]) }}" wire:navigate class="flex items-center justify-between gap-3 border-b border-slate-50 px-4 py-3 last:border-0 dark:border-slate-800/70">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium">{{ $transaction->category?->icon ?? '❔' }} {{ $transaction->description }}</div>
                            <div class="truncate text-xs text-slate-500">{{ $transaction->category?->name ?? 'Sem categoria' }} · {{ $transaction->account?->name }}</div>
                        </div>
                        <span @class(['shrink-0 text-sm font-semibold', 'text-expense' => $transaction->amount_cents < 0, 'text-income' => $transaction->amount_cents >= 0])>{{ Money::format($transaction->amount_cents) }}</span>
                    </a>
                @empty
                    <p class="px-4 py-5 text-sm text-slate-500">Nenhum lançamento. Use “+ Lançar” para registrar uma despesa ou receita.</p>
                @endforelse
            </section>
        @endforeach
    @endif
</div>
