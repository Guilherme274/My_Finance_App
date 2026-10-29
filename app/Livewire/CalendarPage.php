<?php

namespace App\Livewire;

use App\Models\Transaction;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Calendário de Gastos')]
class CalendarPage extends Component
{
    #[Url]
    public string $month = '';

    #[Url]
    public string $filter = 'expenses'; // 'expenses' | 'income' | 'invoices'

    public int $selectedDay = 1;

    public function mount(): void
    {
        if (! $this->month) {
            $sessionMonth = session('finance_selected_month');
            if (is_string($sessionMonth) && preg_match('/^\d{4}-\d{2}$/', $sessionMonth)) {
                $this->month = $sessionMonth;
            } else {
                $currentMonth = now()->format('Y-m');
                $hasCurrent = Transaction::query()->inMonth($currentMonth)->exists();
                if (! $hasCurrent) {
                    $latest = Transaction::orderByDesc('date')->value('date');
                    $this->month = $latest ? Carbon::parse($latest)->format('Y-m') : $currentMonth;
                } else {
                    $this->month = $currentMonth;
                }
            }
        }

        session(['finance_selected_month' => $this->month]);
        $this->selectDefaultDayForFilter();
    }

    protected function selectDefaultDayForFilter(): void
    {
        $query = Transaction::query()->inMonth($this->month);
        if ($this->filter === 'expenses' || $this->filter === 'invoices') {
            $query->where('amount_cents', '<', 0);
        } elseif ($this->filter === 'income') {
            $query->where('amount_cents', '>', 0);
        }

        $firstMatchingDay = $query->orderByDesc('date')->value('date')
            ?? Transaction::query()->inMonth($this->month)->orderByDesc('date')->value('date');

        $this->selectedDay = $firstMatchingDay
            ? (int) Carbon::parse($firstMatchingDay)->day
            : (int) min(now()->day, Carbon::createFromFormat('Y-m', $this->month)->daysInMonth);
    }

    public function previousMonth(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->month)->subMonth();
        $this->month = $dt->format('Y-m');
        session(['finance_selected_month' => $this->month]);
        $this->selectDefaultDayForFilter();
    }

    public function nextMonth(): void
    {
        $dt = Carbon::createFromFormat('Y-m', $this->month)->addMonth();
        $this->month = $dt->format('Y-m');
        session(['finance_selected_month' => $this->month]);
        $this->selectDefaultDayForFilter();
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
        $this->selectDefaultDayForFilter();
    }

    public function selectDay(int $day): void
    {
        $this->selectedDay = $day;
    }

    public function render()
    {
        [$year, $monthNum] = explode('-', $this->month);
        $startOfMonth = Carbon::createFromDate((int) $year, (int) $monthNum, 1)->startOfDay();
        $daysInMonth = $startOfMonth->daysInMonth;

        $monthTransactions = Transaction::with(['category', 'account'])
            ->inMonth($this->month)
            ->orderBy('date')
            ->orderByDesc('created_at')
            ->get();

        // Filter query based on active pill
        $filtered = $monthTransactions->filter(function (Transaction $tx) {
            if ($this->filter === 'expenses') {
                return $tx->amount_cents < 0;
            }
            if ($this->filter === 'income') {
                return $tx->amount_cents > 0;
            }
            if ($this->filter === 'invoices') {
                return $tx->amount_cents < 0 && (
                    optional($tx->account)->type === 'credit_card' || str_contains((string) $tx->source, 'csv')
                );
            }

            return true;
        });

        $totalFilteredCents = (int) $filtered->sum(fn (Transaction $t) => abs($t->amount_cents));
        $elapsedDays = $startOfMonth->isCurrentMonth() ? max(1, now()->day) : $daysInMonth;
        $dailyAverageCents = (int) round($totalFilteredCents / max(1, $elapsedDays));

        // Group by day of month
        $byDay = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $byDay[$d] = [
                'day' => $d,
                'date' => Carbon::createFromDate((int) $year, (int) $monthNum, $d),
                'total_cents' => 0,
                'expense_cents' => 0,
                'income_cents' => 0,
                'count' => 0,
                'items' => [],
            ];
        }

        foreach ($filtered as $tx) {
            $d = (int) $tx->date->day;
            $absCents = abs($tx->amount_cents);
            if (isset($byDay[$d])) {
                $byDay[$d]['total_cents'] += $absCents;
                if ($tx->amount_cents < 0) {
                    $byDay[$d]['expense_cents'] += $absCents;
                } else {
                    $byDay[$d]['income_cents'] += $absCents;
                }
                $byDay[$d]['count']++;
                $byDay[$d]['items'][] = $tx;
            }
        }

        // Previous month trailing days (Monday = 1 ... Sunday = 7)
        $firstDayIso = (int) $startOfMonth->dayOfWeekIso;
        $leadingEmptyCount = $firstDayIso - 1;
        $prevMonthEnd = $startOfMonth->copy()->subDay();
        $leadingDays = [];
        for ($i = $leadingEmptyCount - 1; $i >= 0; $i--) {
            $leadingDays[] = $prevMonthEnd->copy()->subDays($i)->day;
        }

        $monthIncomeCents = (int) $monthTransactions->where('amount_cents', '>', 0)->sum('amount_cents');
        $monthExpenseCents = abs((int) $monthTransactions->where('amount_cents', '<', 0)->sum('amount_cents'));

        $safeSelectedDay = min(max(1, $this->selectedDay), $daysInMonth);
        $selectedDayData = $byDay[$safeSelectedDay] ?? $byDay[1];
        $selectedDate = $selectedDayData['date'];

        $selectedTotalCents = $selectedDayData['total_cents'];
        $diffVsAvgPercent = $dailyAverageCents > 0
            ? round((($selectedTotalCents - $dailyAverageCents) / $dailyAverageCents) * 100)
            : 0;

        $topCategoryOnDay = collect($selectedDayData['items'])
            ->groupBy(fn ($tx) => $tx->category->name ?? 'Sem categoria')
            ->map(fn ($items) => $items->sum(fn ($t) => abs($t->amount_cents)))
            ->sortDesc()
            ->keys()
            ->first();

        return view('livewire.calendar-page', [
            'monthLabel' => ucfirst($startOfMonth->locale('pt_BR')->translatedFormat('F Y')),
            'monthIncomeCents' => $monthIncomeCents,
            'monthExpenseCents' => $monthExpenseCents,
            'totalFilteredCents' => $totalFilteredCents,
            'dailyAverageCents' => $dailyAverageCents,
            'leadingDays' => $leadingDays,
            'byDay' => $byDay,
            'selectedDayData' => $selectedDayData,
            'selectedDateLabel' => ucfirst($selectedDate->locale('pt_BR')->translatedFormat('d \d\e F, l')),
            'diffVsAvgPercent' => $diffVsAvgPercent,
            'topCategoryOnDay' => $topCategoryOnDay,
        ]);
    }
}
