<?php

namespace App\Livewire;

use App\Models\DietPlan;
use App\Models\UserDietAssignment;
use App\Services\CalorieCalculatorService;
use App\Services\NutritionAiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Dietas — DietaIA')]
class DietsPage extends Component
{
    public ?string $suggestionReason = null;

    public function select(int $dietPlanId, CalorieCalculatorService $calories): void
    {
        $user = auth()->user();
        $plan = DietPlan::findOrFail($dietPlanId);

        $user->dietAssignments()->where('is_active', true)->update(['is_active' => false]);
        UserDietAssignment::create([
            'user_id' => $user->id,
            'diet_plan_id' => $plan->id,
            'is_active' => true,
            'source' => 'manual',
            'started_at' => now(),
        ]);

        if ($user->profile) {
            $calories->applyToProfile($user->profile, $plan->macros_ratio);
        }

        session()->flash('status', "Plan «{$plan->name}» activado.");
    }

    public function suggest(NutritionAiService $ai, CalorieCalculatorService $calories): void
    {
        $user = auth()->user()->load('profile');
        $result = $ai->suggestDiet($user);
        $plan = $result['recommended'];
        $this->suggestionReason = $result['reason'] ?? null;

        if ($plan) {
            $user->dietAssignments()->where('is_active', true)->update(['is_active' => false]);
            UserDietAssignment::create([
                'user_id' => $user->id,
                'diet_plan_id' => $plan->id,
                'is_active' => true,
                'source' => 'ai',
                'started_at' => now(),
            ]);
            if ($user->profile) {
                $calories->applyToProfile($user->profile, $plan->macros_ratio);
            }
            session()->flash('status', "IA recomienda: {$plan->name}");
        }
    }

    public function render()
    {
        $plans = DietPlan::query()->where('is_active', true)->orderBy('name')->get();
        $active = auth()->user()->activeDietAssignment?->load('dietPlan');

        return view('livewire.diets-page', compact('plans', 'active'));
    }
}
