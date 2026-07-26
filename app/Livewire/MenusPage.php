<?php

namespace App\Livewire;

use App\Models\WeeklyMenu;
use App\Services\NutritionAiService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Menús — DietaIA')]
class MenusPage extends Component
{
    public ?WeeklyMenu $latest = null;

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

        session()->flash('status', $horizon === 'weekly' ? 'Menú semanal generado.' : 'Menú diario generado.');
    }

    public function render()
    {
        return view('livewire.menus-page');
    }
}
