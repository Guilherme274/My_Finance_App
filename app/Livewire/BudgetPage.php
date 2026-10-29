<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Transaction;
use App\Support\BankBrand;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Orçamento & Comparativo')]
class BudgetPage extends Component
{
    #[Url]
    public string $month = '';

    public string $activeTab = 'all'; // 'all' | 'comparativo' | 'categorias'

    public bool $editingBudgets = false;

    public array $budgetInputs = [];

    public ?string $savedNotice = null;

    public function mount(): void
    {
        if (! $this->month) {
            $sessionMonth = session('finance_selected_month');
            if (is_string($sessionMonth) && preg_match('/^\d{4}-\d{2}$/', $sessionMonth)) {
                $this->month = $sessionMonth;
            } else {
                $currentMonth = now()->format('Y-m');
                $hasCurrent = Transaction::query()->inMonth($currentMonth)->exists();
                $latest = Transaction::orderByDesc('date')->value('date');
                $this->month = (! $hasCurrent && $latest)
                    ? Carbon::parse($latest)->format('Y-m')
                    : $currentMonth;
            }
        }

        session(['finance_selected_month' => $this->month]);
        $this->loadBudgetInputs();
    }

    protected function loadBudgetInputs(): void
    {
        $categories = Category::orderBy('name')->get();
        foreach ($categories as $cat) {
            $defaultCents = $cat->monthly_budget_cents ?: 80000;
            $this->budgetInputs[$cat->id] = number_format($defaultCents / 100, 2, ',', '');
        }
    }

    public function previousMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->subMonth()->format('Y-m');
        session(['finance_selected_month' => $this->month]);
    }

    public function nextMonth(): void
    {
        $this->month = Carbon::createFromFormat('Y-m', $this->month)->addMonth()->format('Y-m');
        session(['finance_selected_month' => $this->month]);
    }

    public function toggleEditBudgets(): void
    {
        $this->editingBudgets = ! $this->editingBudgets;
        $this->savedNotice = null;
    }

    public function saveBudgets(): void
    {
        foreach ($this->budgetInputs as $categoryId => $val) {
            $clean = str_replace(['.', ','], ['', '.'], (string) $val);
            $cents = max(0, (int) round(((float) $clean) * 100));
            Category::where('id', $categoryId)->update(['monthly_budget_cents' => $cents]);
        }

        $this->editingBudgets = false;
        $this->savedNotice = 'Metas mensais atualizadas com sucesso!';
    }

    public function exportCsv(): StreamedResponse
    {
        $transactions = Transaction::with(['category', 'account'])
            ->inMonth($this->month)
            ->orderBy('date')
            ->get();

        $filename = "relatorio-orcamento-{$this->month}.csv";

        return response()->streamDownload(function () use ($transactions) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Tipo', 'Categoria', 'Conta', 'Descricao', 'Valor (R$)'], ';');
            foreach ($transactions as $tx) {
                fputcsv($out, [
                    $tx->date->format('d/m/Y'),
                    $tx->amount_cents >= 0 ? 'Receita' : 'Despesa',
                    $tx->category->name ?? 'Sem categoria',
                    $tx->account->name ?? 'Conta',
                    $tx->description,
                    number_format(abs($tx->amount_cents) / 100, 2, ',', '.'),
                ], ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render()
    {
        [$year, $monthNum] = explode('-', $this->month);
        $currentStart = Carbon::createFromDate((int) $year, (int) $monthNum, 1)->startOfDay();
        $prevStart = $currentStart->copy()->subMonth()->startOfMonth();

        $currentMonthTx = Transaction::with(['category', 'account'])
            ->inMonth($this->month)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        $prevMonthTx = Transaction::query()
            ->inMonth($prevStart->format('Y-m'))
            ->get();

        $currentIncomeCents = (int) $currentMonthTx->where('amount_cents', '>', 0)->sum('amount_cents');
        $currentExpenseCents = abs((int) $currentMonthTx->where('amount_cents', '<', 0)->sum('amount_cents'));
        $prevExpenseCents = abs((int) $prevMonthTx->where('amount_cents', '<', 0)->sum('amount_cents'));

        if ($prevExpenseCents > 0) {
            $efficiencyDiffPercent = round((($currentExpenseCents - $prevExpenseCents) / $prevExpenseCents) * 100, 1);
            $savingsDiffCents = $prevExpenseCents - $currentExpenseCents;
        } else {
            $efficiencyDiffPercent = 0.0;
            $savingsDiffCents = 0;
        }

        // 4-Month Comparative Evolution
        $monthsComparison = [];
        $maxBarCents = 100000;
        for ($i = 3; $i >= 0; $i--) {
            $mStart = $currentStart->copy()->subMonths($i)->startOfMonth();
            $txs = Transaction::query()
                ->inMonth($mStart->format('Y-m'))
                ->get();
            $inc = (int) $txs->where('amount_cents', '>', 0)->sum('amount_cents');
            $exp = abs((int) $txs->where('amount_cents', '<', 0)->sum('amount_cents'));
            $maxBarCents = max($maxBarCents, $inc, $exp);

            $monthsComparison[] = [
                'label' => ucfirst($mStart->locale('pt_BR')->translatedFormat('M')),
                'is_current' => $i === 0,
                'income_cents' => $inc,
                'expense_cents' => $exp,
            ];
        }

        foreach ($monthsComparison as &$mc) {
            $mc['income_height_pct'] = $maxBarCents > 0 ? max(10, min(100, round(($mc['income_cents'] / $maxBarCents) * 100))) : 15;
            $mc['expense_height_pct'] = $maxBarCents > 0 ? max(10, min(100, round(($mc['expense_cents'] / $maxBarCents) * 100))) : 15;
        }
        unset($mc);

        // Category Budgets & Operational Limits
        $txsByCategory = $currentMonthTx->groupBy(fn ($t) => (string) ($t->category_id ?? '__none__'));
        $spentByCategory = $txsByCategory->map(fn ($items) => abs((int) $items->where('amount_cents', '<', 0)->sum('amount_cents')));

        $categories = Category::orderBy('name')->get();
        $categoriesById = $categories->keyBy('id');

        // All categories that have spending this month OR are marked as expense
        $allCatKeys = collect($categories->pluck('id'))
            ->merge($spentByCategory->keys())
            ->unique();

        $categoryLimits = $allCatKeys->map(function ($catKey) use ($categoriesById, $spentByCategory, $txsByCategory) {
            $spent = $spentByCategory->get($catKey, 0);
            $rawTxs = $txsByCategory->get($catKey, collect());

            $transactions = $rawTxs->map(function ($t) {
                $parsed = BankBrand::parse($t);

                return [
                    'id' => (string) $t->id,
                    'date_formatted' => $t->date ? $t->date->format('d/m') : '',
                    'description' => $t->description,
                    'clean_title' => $parsed['cleanDescription'],
                    'bank_brand' => $parsed['brand'],
                    'amount_cents' => (int) $t->amount_cents,
                    'amount_formatted' => number_format(abs($t->amount_cents) / 100, 2, ',', '.'),
                    'account_name' => $t->account?->name ?? 'Conta',
                ];
            })->values()->all();

            if ($catKey === '__none__') {
                return [
                    'id' => '__none__',
                    'name' => 'Sem categoria',
                    'spent_cents' => $spent,
                    'budget_cents' => 50000,
                    'pct' => $spent > 0 ? 100 : 0,
                    'exceeded_cents' => max(0, $spent - 50000),
                    'remaining_cents' => max(0, 50000 - $spent),
                    'status' => $spent > 50000 ? 'exceeded' : 'ok',
                    'transactions' => $transactions,
                ];
            }

            $cat = $categoriesById->get($catKey);
            if (! $cat) {
                return null;
            }

            $budget = $cat->monthly_budget_cents > 0 ? (int) $cat->monthly_budget_cents : 60000;
            $pct = $budget > 0 ? round(($spent / $budget) * 100) : 0;

            return [
                'id' => (string) $cat->id,
                'name' => $cat->name,
                'spent_cents' => $spent,
                'budget_cents' => $budget,
                'pct' => $pct,
                'exceeded_cents' => max(0, $spent - $budget),
                'remaining_cents' => max(0, $budget - $spent),
                'status' => $pct > 100 ? 'exceeded' : ($pct >= 80 ? 'warning' : 'ok'),
                'transactions' => $transactions,
            ];
        })->filter()->sortByDesc('spent_cents')->values();

        // 50 / 30 / 20 Rule Calculation
        $essentialNames = ['alimentação', 'mercado', 'moradia', 'saúde', 'transporte', 'educação', 'contas'];
        $essentialCents = 0;
        $lifestyleCents = 0;

        foreach ($currentMonthTx->where('amount_cents', '<', 0) as $tx) {
            $catLower = mb_strtolower($tx->category->name ?? '');
            $isEssential = false;
            foreach ($essentialNames as $kw) {
                if (str_contains($catLower, $kw)) {
                    $isEssential = true;
                    break;
                }
            }
            if ($isEssential) {
                $essentialCents += abs($tx->amount_cents);
            } else {
                $lifestyleCents += abs($tx->amount_cents);
            }
        }

        $investedSurplusCents = max(0, $currentIncomeCents - $currentExpenseCents);
        $baseRuleTotal = max(1, $essentialCents + $lifestyleCents + $investedSurplusCents);

        $essentialPct = (int) round(($essentialCents / $baseRuleTotal) * 100);
        $lifestylePct = (int) round(($lifestyleCents / $baseRuleTotal) * 100);
        $investPct = max(0, 100 - $essentialPct - $lifestylePct);

        return view('livewire.budget-page', [
            'currentMonthLabel' => ucfirst($currentStart->locale('pt_BR')->translatedFormat('M Y')),
            'prevMonthLabel' => ucfirst($prevStart->locale('pt_BR')->translatedFormat('M Y')),
            'currentIncomeCents' => $currentIncomeCents,
            'currentExpenseCents' => $currentExpenseCents,
            'prevExpenseCents' => $prevExpenseCents,
            'efficiencyDiffPercent' => $efficiencyDiffPercent,
            'savingsDiffCents' => $savingsDiffCents,
            'monthsComparison' => $monthsComparison,
            'categoryLimits' => $categoryLimits,
            'essentialPct' => $essentialPct,
            'lifestylePct' => $lifestylePct,
            'investPct' => $investPct,
        ]);
    }
}
