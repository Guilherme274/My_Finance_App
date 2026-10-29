<?php

namespace App\Models;

use App\Models\Concerns\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Syncable;

    public const KINDS = [
        'expense' => 'Despesa',
        'income' => 'Receita',
        'transfer' => 'Transferência',
    ];

    protected $fillable = ['name', 'kind', 'color', 'icon', 'parent_id', 'monthly_budget_cents', 'monthly_budget_percent', 'include_in_budget', 'budget_allocation'];

    protected function casts(): array
    {
        return [
            'monthly_budget_cents' => 'integer',
            'monthly_budget_percent' => 'float',
            'include_in_budget' => 'boolean',
        ];
    }

    /**
     * Retorna a classificação na regra 50/30/20: essential, lifestyle ou investment.
     */
    public function getAllocationGroup(): string
    {
        if ($this->budget_allocation) {
            return $this->budget_allocation;
        }

        $essentialNames = ['alimentação', 'mercado', 'moradia', 'saúde', 'transporte', 'educação', 'contas'];
        $nameLower = mb_strtolower($this->name);
        foreach ($essentialNames as $kw) {
            if (str_contains($nameLower, $kw)) {
                return 'essential';
            }
        }

        return 'lifestyle';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function rules(): HasMany
    {
        return $this->hasMany(CategoryRule::class);
    }
}
