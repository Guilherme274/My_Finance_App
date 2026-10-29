<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;

class Investment extends Model
{
    use Syncable;

    public const CLASSES = [
        'emergency' => ['label' => 'Reserva Líquida', 'short' => 'Reserva', 'color' => '#3eb686', 'dot' => 'bg-tertiary-container'],
        'fixed_income' => ['label' => 'Renda Fixa', 'short' => 'RF', 'color' => '#4edea3', 'dot' => 'bg-primary'],
        'stocks_fii' => ['label' => 'Ações & FIIs', 'short' => 'FII/ETF', 'color' => '#00bd85', 'dot' => 'bg-secondary-container'],
        'crypto_global' => ['label' => 'Cripto & Global', 'short' => 'Cripto', 'color' => '#85f8c4', 'dot' => 'bg-tertiary-fixed'],
    ];

    protected $fillable = [
        'name',
        'ticker',
        'asset_class',
        'subtitle',
        'balance_cents',
        'target_cents',
        'daily_change_pct',
        'icon',
    ];

    protected function casts(): array
    {
        return [
            'balance_cents' => 'integer',
            'target_cents' => 'integer',
            'daily_change_pct' => 'float',
        ];
    }
}
