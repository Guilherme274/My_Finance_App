<?php

namespace App\Livewire;

use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Calendário')]
class Calendar extends Component
{
    #[Url]
    public string $mode = 'month';

    #[Url]
    public string $date = '';

    public function mount(): void
    {
        $this->date = $this->date ?: now()->toDateString();
        $this->validate(['mode' => [Rule::in(['month', 'week', 'day'])], 'date' => ['date']]);
    }

    public function setMode(string $mode): void
    {
        $this->validate(['mode' => [Rule::in(['month', 'week', 'day'])]]);
        $this->mode = $mode;
    }

    public function movePeriod(int $direction): void
    {
        $selectedDate = CarbonImmutable::parse($this->date);
        $this->date = match ($this->mode) {
            'week' => $selectedDate->addWeeks($direction)->toDateString(),
            'day' => $selectedDate->addDays($direction)->toDateString(),
            default => $selectedDate->addMonths($direction)->toDateString(),
        };
    }

    public function selectDate(string $date): void
    {
        $this->date = CarbonImmutable::parse($date)->toDateString();
        $this->mode = 'day';
    }

    public function render()
    {
        $selectedDate = CarbonImmutable::parse($this->date);
        [$periodStart, $periodEnd] = match ($this->mode) {
            'week' => [$selectedDate->startOfWeek(), $selectedDate->endOfWeek()],
            'day' => [$selectedDate, $selectedDate],
            default => [$selectedDate->startOfMonth(), $selectedDate->endOfMonth()],
        };

        $transactionsByDate = Transaction::with(['category', 'account'])
            ->whereBetween('date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->orderBy('date')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy(fn (Transaction $transaction) => $transaction->date->toDateString());

        $gridStart = $selectedDate->startOfMonth()->startOfWeek();
        $gridEnd = $selectedDate->endOfMonth()->endOfWeek();
        $monthDays = collect();
        for ($day = $gridStart; $day->lte($gridEnd); $day = $day->addDay()) {
            $monthDays->push($day);
        }

        return view('livewire.calendar', [
            'selectedDate' => $selectedDate,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
            'transactionsByDate' => $transactionsByDate,
            'monthDays' => $monthDays,
            'weekDays' => collect(range(0, 6))->map(fn (int $offset) => $selectedDate->startOfWeek()->addDays($offset)),
            'weekLabels' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
            'monthLabel' => $selectedDate->locale('pt_BR')->translatedFormat('F \d\e Y'),
            'periodLabel' => $this->mode === 'day'
                ? $selectedDate->locale('pt_BR')->translatedFormat('l, d \d\e F \d\e Y')
                : $periodStart->format('d/m').' — '.$periodEnd->format('d/m/Y'),
        ]);
    }
}
