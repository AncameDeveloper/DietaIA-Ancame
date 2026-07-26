<?php

namespace App\Livewire;

use App\Services\CalorieCalculatorService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Perfil — DietaIA')]
class ProfileForm extends Component
{
    public string $name = '';
    public ?int $age = null;
    public string $sex = 'other';
    public ?float $weight_kg = null;
    public ?float $start_weight_kg = null;
    public ?float $target_weight_kg = null;
    public ?float $height_cm = null;
    public string $activity_level = 'sedentary';
    public string $goal = 'lose_weight';
    public string $allergies_text = '';
    public string $restrictions_text = '';

    public function mount(): void
    {
        $user = auth()->user()->load('profile');
        $p = $user->profile;
        $this->name = $user->name;
        $this->age = $p?->age;
        $this->sex = $p?->sex ?? 'other';
        $this->weight_kg = $p?->weight_kg ? (float) $p->weight_kg : null;
        $this->start_weight_kg = $p?->start_weight_kg ? (float) $p->start_weight_kg : $this->weight_kg;
        $this->target_weight_kg = $p?->target_weight_kg ? (float) $p->target_weight_kg : null;
        $this->height_cm = $p?->height_cm ? (float) $p->height_cm : null;
        $this->activity_level = $p?->activity_level ?? 'sedentary';
        $this->goal = $p?->goal ?? 'lose_weight';
        $this->allergies_text = implode(', ', $p?->allergies ?? []);
        $this->restrictions_text = implode(', ', $p?->restrictions ?? []);
    }

    public function save(CalorieCalculatorService $calories): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'age' => ['required', 'integer', 'min:12', 'max:100'],
            'sex' => ['required', 'in:male,female,other'],
            'weight_kg' => ['required', 'numeric', 'min:30', 'max:300'],
            'start_weight_kg' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'target_weight_kg' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'height_cm' => ['required', 'numeric', 'min:100', 'max:250'],
            'activity_level' => ['required', 'in:sedentary,light,moderate,active,very_active'],
            'goal' => ['required', 'in:lose_weight,maintain,gain_muscle'],
            'allergies_text' => ['nullable', 'string'],
            'restrictions_text' => ['nullable', 'string'],
        ]);

        $user = auth()->user();
        $user->update(['name' => $data['name']]);

        $profile = $user->profile()->firstOrCreate([]);
        $profile->fill([
            'age' => $data['age'],
            'sex' => $data['sex'],
            'weight_kg' => $data['weight_kg'],
            'start_weight_kg' => $data['start_weight_kg'] ?? $profile->start_weight_kg ?? $data['weight_kg'],
            'target_weight_kg' => $data['target_weight_kg'] ?? $profile->target_weight_kg,
            'height_cm' => $data['height_cm'],
            'activity_level' => $data['activity_level'],
            'goal' => $data['goal'],
            'allergies' => $this->splitList($this->allergies_text),
            'restrictions' => $this->splitList($this->restrictions_text),
            'onboarding_completed' => true,
        ]);

        $macros = $user->activeDietAssignment?->dietPlan?->macros_ratio;
        $calories->applyToProfile($profile, $macros);

        session()->flash('status', 'Perfil actualizado. Objetivos recalculados.');
        $this->redirect(route('dashboard'), navigate: true);
    }

    private function splitList(string $text): array
    {
        return collect(explode(',', $text))
            ->map(fn ($i) => trim($i))
            ->filter()
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.profile-form');
    }
}
