<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Services\CategoryMatcher;
use App\Support\MaterialIcon;
use App\Support\Money;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Categorias')]
class Categories extends Component
{
    public bool $showForm = false;

    public ?string $editingId = null;

    public string $name = '';

    public string $kind = 'expense';

    public string $color = '#64748b';

    public string $icon = '';

    public string $budget = '';

    public string $budgetPercent = '';

    public bool $includeInBudget = true;

    public string $newPattern = '';

    public function create(): void
    {
        $this->resetValidation();
        $this->reset(['editingId', 'name', 'kind', 'color', 'icon', 'budget', 'budgetPercent', 'newPattern']);
        $this->includeInBudget = true;
        $this->showForm = true;
    }

    public function updatedKind(string $value): void
    {
        if ($value === 'transfer') {
            $this->includeInBudget = false;
        }
    }

    public function edit(string $id): void
    {
        $c = Category::findOrFail($id);
        $this->resetValidation();
        $this->editingId = $c->id;
        $this->name = $c->name;
        $this->kind = $c->kind;
        $this->color = $c->color;
        $this->includeInBudget = (bool) $c->include_in_budget;
        $icon = (string) $c->icon;
        if (! preg_match('/^[a-z0-9_]+$/', $icon)) {
            $icon = MaterialIcon::forCategory($c->name, $c->kind);
        }
        $this->icon = $icon;
        $this->budget = $c->monthly_budget_cents ? Money::format($c->monthly_budget_cents, false) : '';
        $this->budgetPercent = $c->monthly_budget_percent !== null ? number_format($c->monthly_budget_percent, 2, '.', '') : '';
        $this->newPattern = '';
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'kind' => ['required', Rule::in(array_keys(Category::KINDS))],
            'color' => ['required', 'string', 'max:9'],
            'icon' => ['nullable', 'string', 'max:16'],
            'budgetPercent' => ['nullable', 'numeric', 'min:0.01', 'max:100'],
            'includeInBudget' => ['boolean'],
        ]);

        $budget = null;
        if ($this->budgetPercent === '' && $this->budget !== '') {
            try {
                $budget = abs(Money::parse($this->budget));
            } catch (InvalidArgumentException) {
                $this->addError('budget', 'Valor inválido.');

                return;
            }
        }

        $icon = trim($this->icon);
        if (! preg_match('/^[a-z0-9_]+$/', $icon)) {
            $icon = MaterialIcon::forCategory($this->name, $this->kind);
        }

        $category = Category::updateOrCreate(['id' => $this->editingId], [
            'name' => $this->name,
            'kind' => $this->kind,
            'color' => $this->color,
            'icon' => $icon ?: null,
            'monthly_budget_cents' => $this->budgetPercent !== '' ? null : $budget,
            'monthly_budget_percent' => $this->budgetPercent !== '' ? (float) str_replace(',', '.', $this->budgetPercent) : null,
            'include_in_budget' => $this->includeInBudget,
        ]);

        $this->editingId = $category->id;
        session()->flash('status', 'Categoria salva.');
    }

    public function addRule(): void
    {
        $this->validate(['newPattern' => ['required', 'string', 'min:2', 'max:100']]);

        if (! $this->editingId) {
            $this->save();
        }

        CategoryRule::create(['pattern' => trim($this->newPattern), 'category_id' => $this->editingId, 'priority' => 0]);
        $this->newPattern = '';
    }

    public function removeRule(string $id): void
    {
        CategoryRule::findOrFail($id)->delete();
    }

    public function delete(string $id): void
    {
        $category = Category::findOrFail($id);
        Transaction::where('category_id', $id)->update(['category_id' => null]);
        $category->rules()->get()->each->delete();
        $category->delete();
        $this->showForm = false;
        session()->flash('status', 'Categoria excluída.');
    }

    /**
     * Aplica as regras aos lançamentos que ainda estão sem categoria.
     */
    public function applyRules(CategoryMatcher $matcher): void
    {
        $count = 0;
        Transaction::whereNull('category_id')->get()->each(function (Transaction $t) use ($matcher, &$count) {
            if ($categoryId = $matcher->match($t->description)) {
                $t->update(['category_id' => $categoryId]);
                $count++;
            }
        });

        session()->flash('status', "{$count} lançamento(s) categorizado(s) automaticamente.");
    }

    public function render()
    {
        return view('livewire.categories', [
            'groups' => Category::withCount('transactions')->orderBy('name')->get()->groupBy('kind'),
            'rules' => $this->editingId ? CategoryRule::where('category_id', $this->editingId)->orderBy('pattern')->get() : collect(),
            'uncategorized' => Transaction::whereNull('category_id')->count(),
        ]);
    }
}
