<?php

namespace App\Services;

use App\Models\CategoryRule;
use App\Support\Text;
use Illuminate\Support\Collection;

/**
 * Sugere a categoria de um lançamento a partir das regras cadastradas
 * (trecho da descrição => categoria). A regra de maior prioridade vence;
 * em empate, a de padrão mais longo (mais específica).
 */
class CategoryMatcher
{
    /** @var Collection<int, array{pattern: string, category_id: string}>|null */
    private ?Collection $rules = null;

    public function match(string $description): ?string
    {
        $normalized = Text::normalize($description);

        foreach ($this->rules() as $rule) {
            if ($rule['pattern'] !== '' && str_contains($normalized, $rule['pattern'])) {
                return $rule['category_id'];
            }
        }

        return null;
    }

    private function rules(): Collection
    {
        return $this->rules ??= CategoryRule::query()
            ->get(['pattern', 'category_id', 'priority'])
            ->map(fn (CategoryRule $r) => [
                'pattern' => Text::normalize($r->pattern),
                'category_id' => $r->category_id,
                'priority' => $r->priority,
            ])
            ->sortBy([['priority', 'desc'], fn ($a, $b) => strlen($b['pattern']) <=> strlen($a['pattern'])])
            ->values();
    }
}
