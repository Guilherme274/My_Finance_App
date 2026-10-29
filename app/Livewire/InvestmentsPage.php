<?php

namespace App\Livewire;

use App\Models\Investment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Investimentos & Reserva')]
class InvestmentsPage extends Component
{
    public bool $hideBalances = false;

    public bool $showContributionModal = false;

    public bool $showNewAssetModal = false;

    public ?string $selectedInvestmentId = null;

    public string $contributionAmountInput = '500,00';

    // New/Edit Asset fields
    public ?string $editingAssetId = null;

    public string $assetName = '';

    public string $assetTicker = '';

    public string $assetClass = 'fixed_income';

    public string $assetSubtitle = 'Nubank / B3 • Liquidez D+1';

    public string $assetBalanceInput = '0,00';

    public string $assetTargetInput = '0,00';

    public string $assetYieldInput = '0';

    public ?string $toastMessage = null;

    public function togglePrivacy(): void
    {
        $this->hideBalances = ! $this->hideBalances;
    }

    public function openReserveContribution(): void
    {
        $reserve = Investment::where('asset_class', 'emergency')->first();
        if ($reserve) {
            $this->selectedInvestmentId = $reserve->id;
            $this->contributionAmountInput = '500,00';
            $this->showContributionModal = true;
        } else {
            $this->openNewAssetModal('emergency');
        }
    }

    public function openContributionFor(string $id): void
    {
        $this->selectedInvestmentId = $id;
        $this->contributionAmountInput = '500,00';
        $this->showContributionModal = true;
    }

    public function confirmContribution(): void
    {
        $inv = Investment::find($this->selectedInvestmentId);
        if (! $inv) {
            $this->showContributionModal = false;

            return;
        }

        $clean = str_replace(['.', ','], ['', '.'], $this->contributionAmountInput);
        $deltaCents = max(0, (int) round(((float) $clean) * 100));

        if ($deltaCents > 0) {
            $inv->update([
                'balance_cents' => $inv->balance_cents + $deltaCents,
            ]);
            $this->toastMessage = 'Aporte de R$ '.number_format($deltaCents / 100, 2, ',', '.')." adicionado em {$inv->name}!";
        }

        $this->showContributionModal = false;
    }

    public function openNewAssetModal(string $defaultClass = 'fixed_income'): void
    {
        $this->editingAssetId = null;
        $this->assetName = '';
        $this->assetTicker = '';
        $this->assetClass = $defaultClass;
        $this->assetSubtitle = $defaultClass === 'emergency' ? 'Tesouro Selic / CDB 100% CDI • Liquidez D+0' : 'Corretora • Liquidez D+1';
        $this->assetBalanceInput = '0,00';
        $this->assetTargetInput = '0,00';
        $this->assetYieldInput = '0';
        $this->showNewAssetModal = true;
    }

    public function editAsset(string $id): void
    {
        $inv = Investment::find($id);
        if (! $inv) {
            return;
        }

        $this->editingAssetId = $inv->id;
        $this->assetName = $inv->name;
        $this->assetTicker = (string) $inv->ticker;
        $this->assetClass = $inv->asset_class;
        $this->assetSubtitle = (string) $inv->subtitle;
        $this->assetBalanceInput = number_format($inv->balance_cents / 100, 2, ',', '');
        $this->assetTargetInput = number_format(($inv->target_cents ?? 0) / 100, 2, ',', '');
        $this->assetYieldInput = number_format((float) $inv->daily_change_pct, 2, '.', '');
        $this->showNewAssetModal = true;
    }

    public function saveAsset(): void
    {
        if (trim($this->assetName) === '') {
            return;
        }

        $balanceCents = max(0, (int) round(((float) str_replace(['.', ','], ['', '.'], $this->assetBalanceInput)) * 100));
        $targetCents = max(0, (int) round(((float) str_replace(['.', ','], ['', '.'], $this->assetTargetInput)) * 100));
        $yieldPct = (float) str_replace(',', '.', $this->assetYieldInput);

        $iconMap = [
            'emergency' => 'shield_with_heart',
            'fixed_income' => 'account_balance',
            'stocks_fii' => 'domain',
            'crypto_global' => 'currency_bitcoin',
        ];

        $data = [
            'name' => $this->assetName,
            'ticker' => $this->assetTicker ?: strtoupper(substr($this->assetName, 0, 6)),
            'asset_class' => $this->assetClass,
            'subtitle' => $this->assetSubtitle ?: 'Carteira Principal',
            'balance_cents' => $balanceCents,
            'target_cents' => $targetCents > 0 ? $targetCents : null,
            'daily_change_pct' => $yieldPct,
            'icon' => $iconMap[$this->assetClass] ?? 'monitoring',
        ];

        if ($this->editingAssetId) {
            Investment::where('id', $this->editingAssetId)->update($data);
            $this->toastMessage = "Ativo {$this->assetName} atualizado com sucesso!";
        } else {
            Investment::create($data);
            $this->toastMessage = "Novo ativo {$this->assetName} adicionado à carteira!";
        }

        $this->showNewAssetModal = false;
    }

    public function deleteAsset(string $id): void
    {
        Investment::where('id', $id)->delete();
        $this->showNewAssetModal = false;
        $this->toastMessage = 'Ativo removido da carteira.';
    }

    public function render()
    {
        $investments = Investment::orderByDesc('balance_cents')->get();

        $totalPortfolioCents = (int) $investments->sum('balance_cents');
        $accumulatedProfitCents = 0;

        $weightedMonthlyYield = $totalPortfolioCents > 0
            ? $investments->sum(fn ($i) => $i->balance_cents * max(0.5, (float) $i->daily_change_pct)) / $totalPortfolioCents
            : 0;

        $monthlyProfitEstimateCents = (int) round($totalPortfolioCents * ($weightedMonthlyYield / 100));

        // Emergency Reserve
        $reserve = $investments->where('asset_class', 'emergency')->first();
        $reserveBalanceCents = $reserve ? (int) $reserve->balance_cents : 0;
        $reserveTargetCents = $reserve && $reserve->target_cents > 0 ? (int) $reserve->target_cents : 0;
        $reservePercent = $reserveTargetCents > 0 ? min(100, (int) round(($reserveBalanceCents / $reserveTargetCents) * 100)) : 0;
        $reserveMissingCents = max(0, $reserveTargetCents - $reserveBalanceCents);
        $reserveMonthlyYieldCents = (int) round($reserveBalanceCents * (($reserve->daily_change_pct ?? 0) / 100));
        $monthsCovered = $reserveTargetCents > 0 ? round(($reserveBalanceCents / $reserveTargetCents) * 6, 1) : 0;

        // Allocation by class for SVG Donut chart (circumference = 2 * pi * 38 ≈ 238.76)
        $circumference = 238.76;
        $classMeta = [
            'fixed_income' => ['label' => 'Renda Fixa (Tesouro / CDB)', 'stroke' => '#4edea3', 'dot' => 'bg-primary'],
            'stocks_fii' => ['label' => 'Ações & FIIs', 'stroke' => '#69daff', 'dot' => 'bg-tertiary'],
            'crypto_global' => ['label' => 'Cripto & Global', 'stroke' => '#e9c349', 'dot' => 'bg-secondary-fixed-dim'],
            'emergency' => ['label' => 'Reserva Líquida', 'stroke' => '#d0bcff', 'dot' => 'bg-purple-400'],
        ];

        $allocations = [];
        $offset = 0.0;
        foreach ($classMeta as $classKey => $meta) {
            $classSum = (int) $investments->where('asset_class', $classKey)->sum('balance_cents');
            $pct = $totalPortfolioCents > 0 ? round(($classSum / $totalPortfolioCents) * 100) : 0;
            $dashLen = round(($pct / 100) * $circumference, 2);

            $allocations[] = [
                'key' => $classKey,
                'label' => $meta['label'],
                'stroke' => $meta['stroke'],
                'dot' => $meta['dot'],
                'balance_cents' => $classSum,
                'pct' => $pct,
                'dasharray' => "{$dashLen} {$circumference}",
                'dashoffset' => -$offset,
            ];
            $offset += $dashLen;
        }

        $holdings = $investments->where('asset_class', '!=', 'emergency')->values();

        return view('livewire.investments-page', [
            'investments' => $investments,
            'holdings' => $holdings,
            'totalPortfolioCents' => $totalPortfolioCents,
            'accumulatedProfitCents' => $accumulatedProfitCents,
            'weightedMonthlyYield' => $weightedMonthlyYield,
            'monthlyProfitEstimateCents' => $monthlyProfitEstimateCents,
            'reserve' => $reserve,
            'reserveBalanceCents' => $reserveBalanceCents,
            'reserveTargetCents' => $reserveTargetCents,
            'reservePercent' => $reservePercent,
            'reserveMissingCents' => $reserveMissingCents,
            'reserveMonthlyYieldCents' => $reserveMonthlyYieldCents,
            'monthsCovered' => $monthsCovered,
            'allocations' => $allocations,
        ]);
    }
}
