<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use Syncable;

    public const TYPES = [
        'checking' => 'Conta corrente',
        'credit_card' => 'Cartão de crédito',
        'wallet' => 'Carteira digital',
        'cash' => 'Dinheiro',
        'investment' => 'Investimento',
    ];

    protected $fillable = [
        'name', 'type', 'initial_balance_cents', 'color', 'closing_day', 'due_day', 'archived',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance_cents' => 'integer',
            'closing_day' => 'integer',
            'due_day' => 'integer',
            'archived' => 'boolean',
        ];
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function balanceCents(): int
    {
        return $this->initial_balance_cents + (int) $this->transactions()->sum('amount_cents');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
