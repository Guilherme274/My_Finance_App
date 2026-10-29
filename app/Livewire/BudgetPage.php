<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Setting;
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

    public string $salaryInput = '';

    public int $salaryCents = 0;

    public bool $editingSalary = false;

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
        $this->salaryCents = (int) Setting::get('budget_salary_cents', 0);
        $this->salaryInput = $this->salaryCents > 0 ? number_format($this->salaryCents / 100, 2, ',', '.') : '';
        $this->loadBudgetInputs();
    }

    protected function loadBudgetInputs(): void
    {
        $categories = Category::where('include_in_budget', true)->orderBy('name')->get();
        $salaryCents = (int) Setting::get('budget_salary_cents', 0);

        foreach ($categories as $cat) {
            if ($cat->monthly_budget_percent !== null && $salaryCents > 0) {
                $calc = (int) round(($salaryCents * $cat->monthly_budget_percent) / 100);
                $this->budgetInputs[$cat->id] = number_format($calc / 100, 2, ',', '');
            } else {
                $defaultCents = $cat->monthly_budget_cents ?: 80000;
                $this->budgetInputs[$cat->id] = number_format($defaultCents / 100, 2, ',', '');
            }
        }
    }

    public function toggleEditSalary(): void
    {
        $this->editingSalary = ! $this->editingSalary;
        if ($this->editingSalary) {
            $this->salaryInput = $this->salaryCents > 0 ? number_format($this->salaryCents / 100, 2, ',', '.') : '';
        }
    }

    public function saveSalary(): void
    {
        $clean = str_replace(['.', ' ', 'R$', 'r$'], '', (string) $this->salaryInput);
        $clean = str_replace(',', '.', $clean);
        $cents = max(0, (int) round(((float) $clean) * 100));

        Setting::put('budget_salary_cents', $cents);
        $this->salaryCents = $cents;
        $this->salaryInput = $cents > 0 ? number_format($cents / 100, 2, ',', '.') : '';
        $this->editingSalary = false;
        $this->loadBudgetInputs();
        $this->savedNotice = 'Salário de referência salvo com sucesso!';
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
            ->includedInBudget()
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

        $categories = Category::where('include_in_budget', true)->orderBy('name')->get();
        $categoriesById = $categories->keyBy('id');

        // Apenas transações que entram no orçamento (sem categoria ou categoria com include_in_budget=true)
        $budgetMonthTx = $currentMonthTx->filter(fn ($t) => ! $t->category || $t->category->include_in_budget);

        $txsByCategory = $budgetMonthTx->groupBy(fn ($t) => (string) ($t->category_id ?? '__none__'));

        // Gasto Líquido por Categoria: abate reembolsos/entradas recebidas na mesma categoria
        $spentByCategory = $txsByCategory->map(function ($items, $catKey) use ($categoriesById) {
            $cat = $catKey === '__none__' ? null : $categoriesById->get($catKey);
            if ($cat && $cat->kind === 'income') {
                return 0;
            }

            $debits = abs((int) $items->where('amount_cents', '<', 0)->sum('amount_cents'));
            $credits = (int) $items->where('amount_cents', '>', 0)->sum('amount_cents');

            return max(0, $debits - $credits);
        });

        $currentExpenseCents = (int) $spentByCategory->sum();

        $currentIncomeCents = (int) $budgetMonthTx->where('amount_cents', '>', 0)
            ->filter(fn ($t) => ! $t->category || $t->category->kind === 'income')
            ->sum('amount_cents');

        $prevMonthTx = Transaction::query()
            ->inMonth($prevStart->format('Y-m'))
            ->includedInBudget()
            ->get();

        $prevTxsByCategory = $prevMonthTx->groupBy(fn ($t) => (string) ($t->category_id ?? '__none__'));
        $prevExpenseCents = (int) $prevTxsByCategory->sum(function ($items, $catKey) use ($categoriesById) {
            $cat = $catKey === '__none__' ? null : $categoriesById->get($catKey);
            if ($cat && $cat->kind === 'income') {
                return 0;
            }
            $debits = abs((int) $items->where('amount_cents', '<', 0)->sum('amount_cents'));
            $credits = (int) $items->where('amount_cents', '>', 0)->sum('amount_cents');

            return max(0, $debits - $credits);
        });

        $referenceSalaryCents = $this->salaryCents > 0 ? $this->salaryCents : max($currentIncomeCents, 1);
        $salarySurplusCents = $referenceSalaryCents - $currentExpenseCents;
        $budgetUsagePct = round(($currentExpenseCents / $referenceSalaryCents) * 100, 1);

        if ($prevExpenseCents > 0) {
            $efficiencyDiffPercent = round((($currentExpenseCents - $prevExpenseCents) / $prevExpenseCents) * 100, 1);
            $savingsDiffCents = $prevExpenseCents - $currentExpenseCents;
        } else {
            $efficiencyDiffPercent = 0.0;
            $savingsDiffCents = 0;
        }

        // 4-Month Comparative Evolution (apenas transações incluídas no orçamento)
        $monthsComparison = [];
        $maxBarCents = max(100000, $referenceSalaryCents);
        for ($i = 3; $i >= 0; $i--) {
            $mStart = $currentStart->copy()->subMonths($i)->startOfMonth();
            $txs = Transaction::query()
                ->inMonth($mStart->format('Y-m'))
                ->includedInBudget()
                ->get();

            $exp = 0;
            foreach ($txs->groupBy(fn ($t) => (string) ($t->category_id ?? '__none__')) as $catKey => $items) {
                $c = $catKey === '__none__' ? null : $categoriesById->get($catKey);
                if ($c && $c->kind === 'income') {
                    continue;
                }
                $d = abs((int) $items->where('amount_cents', '<', 0)->sum('amount_cents'));
                $cr = (int) $items->where('amount_cents', '>', 0)->sum('amount_cents');
                $exp += max(0, $d - $cr);
            }

            $inc = (int) $txs->where('amount_cents', '>', 0)
                ->filter(fn ($t) => ! $t->category || $t->category->kind === 'income')
                ->sum('amount_cents');

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

        // Category Budgets & Operational Limits (apenas categorias incluídas no orçamento)
        $txsByCategory = $budgetMonthTx->groupBy(fn ($t) => (string) ($t->category_id ?? '__none__'));
        $spentByCategory = $txsByCategory->map(fn ($items) => abs((int) $items->where('amount_cents', '<', 0)->sum('amount_cents')));

        $categories = Category::where('include_in_budget', true)->orderBy('name')->get();
        $categoriesById = $categories->keyBy('id');

        $allCatKeys = collect($categories->pluck('id'))
            ->merge($spentByCategory->keys())
            ->unique();

        $categoryLimits = $allCatKeys->map(function ($catKey) use ($categoriesById, $spentByCategory, $txsByCategory, $referenceSalaryCents) {
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
                    'budget_percent' => null,
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

            if ($cat->monthly_budget_percent !== null && $referenceSalaryCents > 0) {
                $budget = (int) round(($referenceSalaryCents * $cat->monthly_budget_percent) / 100);
            } else {
                $budget = $cat->monthly_budget_cents > 0 ? (int) $cat->monthly_budget_cents : 60000;
            }

            $pct = $budget > 0 ? round(($spent / $budget) * 100) : 0;

            return [
                'id' => (string) $cat->id,
                'name' => $cat->name,
                'spent_cents' => $spent,
                'budget_cents' => $budget,
                'budget_percent' => $cat->monthly_budget_percent,
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

        foreach ($txsByCategory as $catKey => $items) {
            $cat = $catKey === '__none__' ? null : $categoriesById->get($catKey);
            if ($cat && $cat->kind === 'income') {
                continue;
            }

            $net = $spentByCategory->get($catKey, 0);
            if ($net <= 0) {
                continue;
            }

            $catLower = mb_strtolower($cat?->name ?? '');
            $isEssential = false;
            foreach ($essentialNames as $kw) {
                if (str_contains($catLower, $kw)) {
                    $isEssential = true;
                    break;
                }
            }

            if ($isEssential) {
                $essentialCents += $net;
            } else {
                $lifestyleCents += $net;
            }
        }

        $baseRuleTotal = $referenceSalaryCents;

        $essentialPct = (int) round(($essentialCents / $baseRuleTotal) * 100);
        $lifestylePct = (int) round(($lifestyleCents / $baseRuleTotal) * 100);
        $investPct = max(0, 100 - $essentialPct - $lifestylePct);

        $essentialTargetCents = (int) round($baseRuleTotal * 0.50);
        $lifestyleTargetCents = (int) round($baseRuleTotal * 0.30);
        $investTargetCents = (int) round($baseRuleTotal * 0.20);
        $investAvailableCents = max(0, $baseRuleTotal - $essentialCents - $lifestyleCents);

        return view('livewire.budget-page', [
            'currentMonthLabel' => ucfirst($currentStart->locale('pt_BR')->translatedFormat('M Y')),
            'prevMonthLabel' => ucfirst($prevStart->locale('pt_BR')->translatedFormat('M Y')),
            'salaryCents' => $this->salaryCents,
            'referenceSalaryCents' => $referenceSalaryCents,
            'salarySurplusCents' => $salarySurplusCents,
            'budgetUsagePct' => $budgetUsagePct,
            'currentIncomeCents' => $currentIncomeCents,
            'currentExpenseCents' => $currentExpenseCents,
            'prevExpenseCents' => $prevExpenseCents,
            'efficiencyDiffPercent' => $efficiencyDiffPercent,
            'savingsDiffCents' => $savingsDiffCents,
            'monthsComparison' => $monthsComparison,
            'categoryLimits' => $categoryLimits,
            'essentialCents' => $essentialCents,
            'lifestyleCents' => $lifestyleCents,
            'essentialTargetCents' => $essentialTargetCents,
            'lifestyleTargetCents' => $lifestyleTargetCents,
            'investTargetCents' => $investTargetCents,
            'investAvailableCents' => $investAvailableCents,
            'essentialPct' => $essentialPct,
            'lifestylePct' => $lifestylePct,
            'investPct' => $investPct,
        ]);
    }
}
