<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Investment;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Início')]
class Dashboard extends Component
{
    #[Url]
    public string $month = '';

    public function mount(): void
    {
        if (! $this->month) {
            $sessionMonth = session('finance_selected_month');
            if (is_string($sessionMonth) && preg_match('/^\d{4}-\d{2}$/', $sessionMonth)) {
                $this->month = $sessionMonth;
            } else {
                $current = now()->format('Y-m');
                $hasCurrent = Transaction::query()->inMonth($current)->exists();
                $latestDate = Transaction::max('date');
                $this->month = (! $hasCurrent && $latestDate)
                    ? CarbonImmutable::parse($latestDate)->format('Y-m')
                    : $current;
            }
        }

        session(['finance_selected_month' => $this->month]);
    }

    public function shiftMonth(int $delta): void
    {
        $this->month = CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01')->addMonths($delta)->format('Y-m');
        session(['finance_selected_month' => $this->month]);
    }

    public function render()
    {
        $categoriesById = Category::all()->keyBy('id');
        $monthQuery = fn () => Transaction::query()->inMonth($this->month)->includedInBudget();

        $monthTxs = $monthQuery()->get();
        $byCategoryMap = $monthTxs->groupBy(fn ($t) => (string) ($t->category_id ?? '__none__'));

        $categoryExpenses = [];
        foreach ($byCategoryMap as $catKey => $items) {
            $category = $catKey === '__none__' ? null : $categoriesById->get($catKey);
            if ($category && $category->kind === 'income') {
                continue;
            }

            $debits = abs((int) $items->where('amount_cents', '<', 0)->sum('amount_cents'));
            $credits = (int) $items->where('amount_cents', '>', 0)->sum('amount_cents');
            $net = max(0, $debits - $credits);

            if ($net > 0) {
                $categoryExpenses[] = [
                    'category' => $category,
                    'name' => $category?->name ?? 'Sem categoria',
                    'color' => $category?->color ?? '#4edea3',
                    'total' => $net,
                    'budget' => $category?->monthly_budget_cents,
                ];
            }
        }

        $expenseAbs = (int) collect($categoryExpenses)->sum('total');
        $expense = -$expenseAbs;

        $income = (int) $monthTxs->where('amount_cents', '>', 0)
            ->filter(fn ($t) => ! $t->category || $t->category->kind === 'income')
            ->sum('amount_cents');

        $previousMonth = CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01')->subMonth()->format('Y-m');
        $prevQuery = fn () => Transaction::query()->inMonth($previousMonth)->includedInBudget();

        $prevTxs = $prevQuery()->get();
        $prevTxsMap = $prevTxs->groupBy(fn ($t) => (string) ($t->category_id ?? '__none__'));
        $previousExpense = (int) $prevTxsMap->sum(function ($items, $catKey) use ($categoriesById) {
            $category = $catKey === '__none__' ? null : $categoriesById->get($catKey);
            if ($category && $category->kind === 'income') {
                return 0;
            }
            $debits = abs((int) $items->where('amount_cents', '<', 0)->sum('amount_cents'));
            $credits = (int) $items->where('amount_cents', '>', 0)->sum('amount_cents');

            return max(0, $debits - $credits);
        });

        $previousIncome = (int) $prevTxs->where('amount_cents', '>', 0)
            ->filter(fn ($t) => ! $t->category || $t->category->kind === 'income')
            ->sum('amount_cents');

        $barColors = ['bg-primary', 'bg-secondary', 'bg-tertiary', 'bg-surface-bright'];

        $byCategory = collect($categoryExpenses)
            ->map(function ($item) use ($expenseAbs) {
                $item['pct'] = $expenseAbs > 0 ? round($item['total'] / $expenseAbs * 100) : 0;

                return $item;
            })
            ->sortByDesc('total')
            ->values()
            ->map(function (array $item, int $idx) use ($barColors) {
                $item['barClass'] = $barColors[$idx % count($barColors)];

                return $item;
            });

        $accounts = Account::where('archived', false)->orderBy('name')->get()
            ->map(fn (Account $a) => ['account' => $a, 'balance' => $a->balanceCents()]);

        $accountsTotal = (int) $accounts->sum('balance');
        $investmentsTotal = (int) Investment::sum('balance_cents');
        $netWorthCents = $accountsTotal + $investmentsTotal;

        $emergency = Investment::where('asset_class', 'emergency')->first();
        $emergencyAccumulated = (int) ($emergency?->balance_cents ?? 0);
        $emergencyTarget = max(1, (int) ($emergency?->target_cents ?? 3000000));
        $emergencyPct = min(100, round($emergencyAccumulated / $emergencyTarget * 100));

        $cdiYieldCents = (int) Transaction::query()
            ->inMonth($this->month)
            ->where('amount_cents', '>', 0)
            ->where(function ($q) {
                $q->where('external_id', 'like', 'mp:yield:%')
                    ->orWhere('description', 'like', '%Rendimento%');
            })
            ->sum('amount_cents');

        $portfolioYieldCents = (int) round(
            Investment::all()->sum(fn ($i) => $i->balance_cents * (max(0, (float) $i->daily_change_pct) / 100))
        );

        $monthlyYieldCents = $cdiYieldCents + $portfolioYieldCents;

        $flowTotal = max(1, $income + $expenseAbs);

        return view('livewire.dashboard', [
            'income' => $income,
            'expense' => $expense,
            'expenseAbs' => $expenseAbs,
            'incomeDiffPct' => $previousIncome > 0 ? round(($income - $previousIncome) / $previousIncome * 100) : null,
            'expenseDiffPct' => $previousExpense > 0 ? round(($expenseAbs - $previousExpense) / $previousExpense * 100) : null,
            'incomeBarPct' => $income > 0 ? max(18, min(100, round($income / $flowTotal * 100))) : 0,
            'expenseBarPct' => $expenseAbs > 0 ? max(18, min(100, round($expenseAbs / $flowTotal * 100))) : 0,
            'byCategory' => $byCategory->take(5),
            'accounts' => $accounts,
            'accountsTotal' => $accountsTotal,
            'investmentsTotal' => $investmentsTotal,
            'netWorthCents' => $netWorthCents,
            'monthlyYieldCents' => $monthlyYieldCents,
            'emergencyAccumulated' => $emergencyAccumulated,
            'emergencyTarget' => $emergencyTarget,
            'emergencyPct' => $emergencyPct,
            'recent' => Transaction::with(['category', 'account'])
                ->inMonth($this->month)
                ->orderByDesc('date')
                ->orderByDesc('created_at')
                ->limit(6)
                ->get(),
            'monthLabel' => CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01')
                ->locale('pt_BR')
                ->translatedFormat('F Y'),
        ]);
    }
}
