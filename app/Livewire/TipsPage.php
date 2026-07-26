<?php

namespace App\Livewire;

use App\Services\NutritionAiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Consejos — DietaIA')]
class TipsPage extends Component
{
    public array $tips = [];

    public function mount(NutritionAiService $ai): void
    {
        $payload = $ai->tips(auth()->user()->load(['profile', 'activeDietAssignment.dietPlan']));
        $this->tips = $payload['tips'] ?? [];
    }

    public function refreshTips(NutritionAiService $ai): void
    {
        $payload = $ai->tips(auth()->user()->load(['profile', 'activeDietAssignment.dietPlan']));
        $this->tips = $payload['tips'] ?? [];
        session()->flash('status', 'Consejos actualizados.');
    }

    public function render()
    {
        return view('livewire.tips-page');
    }
}
