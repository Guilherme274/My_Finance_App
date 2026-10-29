<?php

namespace App\Livewire;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
#[Title('Extrato Completo')]
class Transactions extends Component
{
    use WithPagination;

    #[Url]
    public string $month = '';

    #[Url]
    public string $account = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all'; // 'all' | 'income' | 'expense' | 'pix' | 'uncategorized'

    public bool $showAdvancedFilters = false;

    public int $perPage = 60;

    // Edição em Massa de Categoria
    public bool $bulkMode = false;

    /** @var array<int, string> */
    public array $selectedIds = [];

    public string $bulkCategoryId = '';

    public bool $bulkApplySameDescription = false;

    public function mount(): void
    {
        if (request()->filled('editar') || request()->boolean('novo')) {
            $this->redirect(route('transactions.create', request()->query()), navigate: true);

            return;
        }

        if (! $this->month) {
            $sessionMonth = session('finance_selected_month');
            if (is_string($sessionMonth) && preg_match('/^\d{4}-\d{2}$/', $sessionMonth)) {
                $this->month = $sessionMonth;
            } else {
                $this->month = now()->format('Y-m');
            }
        }

        session(['finance_selected_month' => $this->month]);
    }

    public function updating($property): void
    {
        if (in_array($property, ['month', 'account', 'category', 'search', 'typeFilter'], true)) {
            $this->resetPage();
        }

        if ($property === 'month') {
            session(['finance_selected_month' => $this->month]);
        }
    }

    public function shiftMonth(int $delta): void
    {
        $this->month = CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01')->addMonths($delta)->format('Y-m');
        session(['finance_selected_month' => $this->month]);
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function setQuickFilter(string $filter): void
    {
        if ($filter === 'all') {
            $this->typeFilter = 'all';
            $this->account = '';
            $this->category = '';
        } else {
            $this->typeFilter = $this->typeFilter === $filter ? 'all' : $filter;
        }
        $this->resetPage();
    }

    public function toggleAccountFilter(string $accountId): void
    {
        $this->account = $this->account === $accountId ? '' : $accountId;
        $this->resetPage();
    }

    public function toggleAdvancedFilters(): void
    {
        $this->showAdvancedFilters = ! $this->showAdvancedFilters;
    }

    // --- Ações de Seleção e Categorização em Massa ---

    public function toggleBulkMode(): void
    {
        $this->bulkMode = ! $this->bulkMode;
        if (! $this->bulkMode) {
            $this->selectedIds = [];
            $this->dispatch('selection-reset');
        }
    }

    public function toggleSelectTransaction(string $id): void
    {
        $this->setTransactionSelected($id, ! in_array($id, $this->selectedIds, true));
    }

    public function setTransactionSelected(string $id, bool $selected): void
    {
        $selectedIds = array_values(array_unique(array_map('strval', $this->selectedIds)));
        $selectedIds = array_values(array_filter($selectedIds, fn (string $selectedId) => $selectedId !== $id));

        if ($selected) {
            $selectedIds[] = $id;
        }

        $this->selectedIds = $selectedIds;
        $this->bulkMode = $this->selectedIds !== [];
        $this->dispatch('selection-count-updated', count: count($this->selectedIds), ids: $this->selectedIds);
    }

    /** @param array<int, string> $ids */
    public function syncSelectedTransactions(array $ids): void
    {
        $this->selectedIds = array_values(array_unique(array_map('strval', $ids)));
        $this->bulkMode = $this->selectedIds !== [];
        $this->dispatch('selection-count-updated', count: count($this->selectedIds), ids: $this->selectedIds);
    }

    public function toggleSelectDay(string $dateStr): void
    {
        $this->bulkMode = true;
        $dayIds = (clone $this->buildFilteredQuery())
            ->whereDate('date', $dateStr)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        if ($dayIds === []) {
            return;
        }

        $allSelected = count(array_intersect($dayIds, $this->selectedIds)) === count($dayIds);
        if ($allSelected) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, $dayIds));
        } else {
            $this->selectedIds = array_values(array_unique(array_merge($this->selectedIds, $dayIds)));
        }
        $this->dispatch('selection-count-updated', count: count($this->selectedIds), ids: $this->selectedIds);
    }

    public function selectAllFiltered(): void
    {
        $this->bulkMode = true;
        $this->selectedIds = (clone $this->buildFilteredQuery())
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
        $this->dispatch('selection-count-updated', count: count($this->selectedIds), ids: $this->selectedIds);
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
        $this->dispatch('selection-reset');
    }

    public function applyBulkCategory(?array $overrideIds = null): void
    {
        if ($overrideIds !== null && is_array($overrideIds) && $overrideIds !== []) {
            $this->selectedIds = array_values(array_unique(array_map('strval', $overrideIds)));
        }

        if ($this->selectedIds === []) {
            $this->dispatch('emerald-feedback', message: 'Selecione pelo menos um lançamento na lista.', sound: 'error');

            return;
        }

        if ($this->bulkCategoryId === '') {
            $this->dispatch('emerald-feedback', message: 'Escolha uma categoria para aplicar.', sound: 'error');

            return;
        }

        $targetCategoryId = $this->bulkCategoryId === '__none__' ? null : $this->bulkCategoryId;
        if ($targetCategoryId !== null && ! Category::whereKey($targetCategoryId)->exists()) {
            $this->dispatch('emerald-feedback', message: 'Categoria inválida.', sound: 'error');

            return;
        }

        $selectedTxs = Transaction::whereIn('id', $this->selectedIds)->get(['id', 'description']);
        if ($selectedTxs->isEmpty()) {
            return;
        }

        if ($this->bulkApplySameDescription) {
            $descriptions = $selectedTxs->pluck('description')->unique()->filter()->values()->all();
            $updatedCount = Transaction::where(function ($q) use ($descriptions) {
                $q->whereIn('id', $this->selectedIds);
                if ($descriptions !== []) {
                    $q->orWhereIn('description', $descriptions);
                }
            })->update(['category_id' => $targetCategoryId]);
        } else {
            $updatedCount = Transaction::whereIn('id', $this->selectedIds)
                ->update(['category_id' => $targetCategoryId]);
        }

        $catName = $targetCategoryId ? (Category::find($targetCategoryId)?->name ?? 'Categoria') : 'Sem categoria';

        $this->selectedIds = [];
        $this->bulkMode = false;
        $this->dispatch('selection-reset');

        $this->dispatch(
            'emerald-feedback',
            message: "Categoria \"{$catName}\" aplicada em {$updatedCount} lançamento(s)!",
            sound: 'save'
        );
    }

    public function loadMore(): void
    {
        $this->perPage += 60;
    }

    public function exportCsv(): StreamedResponse
    {
        $items = $this->buildFilteredQuery()->orderByDesc('date')->orderByDesc('created_at')->get();
        $filename = "extrato-completo-{$this->month}.csv";

        return response()->streamDownload(function () use ($items) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM
            fputcsv($out, ['Data', 'Descrição', 'Categoria', 'Conta', 'Tipo', 'Valor (R$)', 'Origem'], ';');

            foreach ($items as $tx) {
                $isInc = $tx->amount_cents >= 0;
                fputcsv($out, [
                    $tx->date->format('d/m/Y'),
                    $tx->description,
                    $tx->category?->name ?? 'Sem categoria',
                    $tx->account?->name ?? 'Conta',
                    $isInc ? 'Receita' : 'Despesa',
                    number_format($tx->amount_cents / 100, 2, ',', '.'),
                    strtoupper((string) $tx->source),
                ], ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    protected function buildFilteredQuery()
    {
        return Transaction::with(['category', 'account'])
            ->inMonth($this->month)
            ->when($this->account, fn ($q) => $q->where('account_id', $this->account))
            ->when($this->category === 'none', fn ($q) => $q->whereNull('category_id'))
            ->when($this->category && $this->category !== 'none', fn ($q) => $q->where('category_id', $this->category))
            ->when($this->typeFilter === 'income', fn ($q) => $q->where('amount_cents', '>', 0))
            ->when($this->typeFilter === 'expense', fn ($q) => $q->where('amount_cents', '<', 0))
            ->when($this->typeFilter === 'pix', fn ($q) => $q->where('description', 'like', '%Pix%'))
            ->when($this->typeFilter === 'uncategorized', fn ($q) => $q->whereNull('category_id'))
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('description', 'like', $term)
                        ->orWhere('notes', 'like', $term)
                        ->orWhereHas('account', fn ($aq) => $aq->where('name', 'like', $term))
                        ->orWhereHas('category', fn ($cq) => $cq->where('name', 'like', $term));
                });
            });
    }

    public function render()
    {
        $query = $this->buildFilteredQuery();

        $totals = (clone $query)->toBase()
            ->selectRaw('COALESCE(SUM(CASE WHEN amount_cents > 0 THEN amount_cents END), 0) as income')
            ->selectRaw('COALESCE(SUM(CASE WHEN amount_cents < 0 THEN amount_cents END), 0) as expense')
            ->selectRaw('COALESCE(SUM(CASE WHEN amount_cents > 0 THEN 1 ELSE 0 END), 0) as income_count')
            ->selectRaw('COALESCE(SUM(CASE WHEN amount_cents < 0 THEN 1 ELSE 0 END), 0) as expense_count')
            ->first();

        $allMonthCount = Transaction::inMonth($this->month)->count();
        $uncategorizedMonthCount = Transaction::inMonth($this->month)->whereNull('category_id')->count();

        $incomeCents = (int) ($totals->income ?? 0);
        $expenseCents = (int) ($totals->expense ?? 0);
        $netCents = $incomeCents + $expenseCents;
        $incomeCount = (int) ($totals->income_count ?? 0);
        $expenseCount = (int) ($totals->expense_count ?? 0);
        $totalFilteredCount = $incomeCount + $expenseCount;

        $netPct = $incomeCents > 0
            ? round(($netCents / $incomeCents) * 100, 1)
            : 0.0;

        $paginated = $query->orderByDesc('date')->orderByDesc('created_at')->paginate($this->perPage);

        $todayStr = CarbonImmutable::today()->toDateString();
        $yesterdayStr = CarbonImmutable::yesterday()->toDateString();

        $grouped = [];
        foreach ($paginated->items() as $tx) {
            $dateStr = $tx->date->toDateString();
            if (! isset($grouped[$dateStr])) {
                $dt = CarbonImmutable::parse($dateStr)->locale('pt_BR');
                $dayMonth = $dt->translatedFormat('d \d\e F');
                $dayMonth = preg_replace_callback('/de ([a-zçáéíóúâêôãõ]+)/u', fn ($m) => 'de '.mb_convert_case($m[1], MB_CASE_TITLE, 'UTF-8'), $dayMonth);

                if ($dateStr === $todayStr) {
                    $label = "Hoje, {$dayMonth}";
                } elseif ($dateStr === $yesterdayStr) {
                    $label = "Ontem, {$dayMonth}";
                } else {
                    $label = $dayMonth;
                }

                $grouped[$dateStr] = [
                    'date_str' => $dateStr,
                    'label' => $label,
                    'net_cents' => 0,
                    'items' => [],
                ];
            }

            $grouped[$dateStr]['net_cents'] += (int) $tx->amount_cents;
            $grouped[$dateStr]['items'][] = $tx;
        }

        $monthDate = CarbonImmutable::createFromFormat('Y-m-d', $this->month.'-01')->locale('pt_BR');
        $monthTitle = mb_convert_case($monthDate->translatedFormat('F Y'), MB_CASE_TITLE, 'UTF-8');

        return view('livewire.transactions', [
            'transactions' => $paginated,
            'groupedTransactions' => array_values($grouped),
            'totals' => $totals,
            'incomeCents' => $incomeCents,
            'expenseCents' => $expenseCents,
            'netCents' => $netCents,
            'incomeCount' => $incomeCount,
            'expenseCount' => $expenseCount,
            'totalFilteredCount' => $totalFilteredCount,
            'allMonthCount' => $allMonthCount,
            'uncategorizedMonthCount' => $uncategorizedMonthCount,
            'netPct' => $netPct,
            'accounts' => Account::where('archived', false)->orderBy('name')->get(),
            'categories' => Category::orderBy('kind')->orderBy('name')->get(),
            'monthLabel' => $monthTitle,
        ]);
    }
}
