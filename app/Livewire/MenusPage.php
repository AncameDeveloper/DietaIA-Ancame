<?php

namespace App\Livewire;

use App\Models\WeeklyMenu;
use App\Services\NutritionAiService;
use App\Services\ShoppingListService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Menús — DietaIA')]
class MenusPage extends Component
{
    public ?WeeklyMenu $latest = null;

    public bool $showShoppingList = false;

    /** @var list<array<string, mixed>> */
    public array $shoppingItems = [];

    /** @var array<int, bool> */
    public array $shoppingChecked = [];

    public string $shoppingError = '';

    public function mount(): void
    {
        $this->latest = WeeklyMenu::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->first();
    }

    public function generate(string $horizon, NutritionAiService $ai): void
    {
        $user = auth()->user()->load(['profile', 'activeDietAssignment.dietPlan']);
        $content = $ai->generateMenu($user, $horizon);

        $this->latest = WeeklyMenu::create([
            'user_id' => $user->id,
            'diet_plan_id' => $user->activeDietAssignment?->diet_plan_id,
            'week_start' => Carbon::now()->startOfWeek()->toDateString(),
            'horizon' => $horizon,
            'content' => $content,
            'notes' => $content['notes'] ?? null,
        ]);

        $this->closeShoppingList();
        session()->flash('status', $horizon === 'weekly' ? 'Menú semanal generado.' : 'Menú diario generado.');
    }

    public function openShoppingList(ShoppingListService $shoppingList): void
    {
        $this->shoppingError = '';

        if (! $this->latest) {
            $this->shoppingError = 'Primero genera un menú diario o semanal.';

            return;
        }

        $content = is_array($this->latest->content) ? $this->latest->content : [];
        $this->shoppingItems = $shoppingList->buildFromMenuContent($content);
        $this->shoppingChecked = [];
        foreach ($this->shoppingItems as $i => $_) {
            $this->shoppingChecked[$i] = false;
        }
        $this->showShoppingList = true;
    }

    public function closeShoppingList(): void
    {
        $this->showShoppingList = false;
        $this->shoppingItems = [];
        $this->shoppingChecked = [];
        $this->shoppingError = '';
    }

    public function toggleShoppingItem(int $index): void
    {
        if (! array_key_exists($index, $this->shoppingChecked)) {
            return;
        }
        $this->shoppingChecked[$index] = ! $this->shoppingChecked[$index];
    }

    public function render()
    {
        return view('livewire.menus-page');
    }
}
