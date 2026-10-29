<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use Syncable;

    protected $fillable = [
        'account_id', 'category_id', 'date', 'description', 'amount_cents',
        'installment', 'installments', 'source', 'external_id', 'hash', 'import_batch_id', 'notes', 'receipt_path',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'amount_cents' => 'integer',
            'installment' => 'integer',
            'installments' => 'integer',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function scopeInMonth(Builder $query, string $month): Builder
    {
        [$start, $end] = [
            CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->startOfMonth(),
            CarbonImmutable::createFromFormat('Y-m-d', $month.'-01')->endOfMonth(),
        ];

        return $query->whereBetween('date', [$start->toDateString(), $end->toDateString()]);
    }

    public function isExpense(): bool
    {
        return $this->amount_cents < 0;
    }

    public function getOccurredOnAttribute()
    {
        return $this->date;
    }

    public function getTypeAttribute(): string
    {
        return $this->amount_cents < 0 ? 'expense' : 'income';
    }
}
