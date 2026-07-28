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

    public bool $fromCache = false;

    public function mount(): void
    {
        $cached = NutritionAiService::cachedTips(auth()->id());
        if ($cached !== null) {
            $this->tips = $cached['tips'] ?? [];
            $this->fromCache = true;
        }
    }

    public function refreshTips(NutritionAiService $ai): void
    {
        $payload = $ai->tips(
            auth()->user()->load(['profile', 'activeDietAssignment.dietPlan']),
            forceRefresh: true
        );
        $this->tips = $payload['tips'] ?? [];
        $this->fromCache = false;
        session()->flash('status', 'Consejos actualizados.');
    }

    public function render()
    {
        return view('livewire.tips-page');
    }
}
