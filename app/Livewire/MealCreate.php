<?php

namespace App\Livewire;

use App\Models\Meal;
use App\Services\DailySummaryService;
use App\Services\NutritionAiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

#[Layout('components.layouts.app')]
#[Title('Registrar comida — DietaIA')]
class MealCreate extends Component
{
    use WithFileUploads;

    public string $description = '';
    public string $meal_type = 'lunch';
    public string $eaten_on = '';
    public $photo = null;
    public string $hint = '';
    public ?array $preview = null;
    public ?int $pendingMealId = null;

    public function mount(): void
    {
        $this->eaten_on = now()->toDateString();
    }

    public function saveText(NutritionAiService $ai, DailySummaryService $summaries): void
    {
        $this->validate([
            'description' => ['required', 'string', 'max:2000'],
            'meal_type' => ['required', 'in:breakfast,lunch,dinner,snack'],
            'eaten_on' => ['required', 'date'],
        ]);

        $user = auth()->user();
        $analysis = $ai->analyzeText($user, $this->description, $this->meal_type);

        $meal = Meal::create([
            'user_id' => $user->id,
            'eaten_on' => $this->eaten_on,
            'meal_type' => $this->meal_type,
            'title' => $analysis['title'] ?? 'Comida',
            'description' => $this->description,
            'source' => 'text_ai',
            'calories' => $analysis['calories'] ?? 0,
            'protein_g' => $analysis['protein_g'] ?? 0,
            'carbs_g' => $analysis['carbs_g'] ?? 0,
            'fat_g' => $analysis['fat_g'] ?? 0,
            'fiber_g' => $analysis['fiber_g'] ?? 0,
            'micros' => $analysis['micros'] ?? [],
            'ai_confidence' => $analysis['confidence'] ?? null,
            'confirmed' => true,
        ]);

        foreach (($analysis['items'] ?? []) as $item) {
            $meal->items()->create([
                'name' => $item['name'] ?? 'Item',
                'quantity_g' => $item['quantity_g'] ?? 100,
                'calories' => $item['calories'] ?? 0,
                'protein_g' => $item['protein_g'] ?? 0,
                'carbs_g' => $item['carbs_g'] ?? 0,
                'fat_g' => $item['fat_g'] ?? 0,
            ]);
        }

        $summaries->rebuild($user, $this->eaten_on);
        $note = (($analysis['confidence'] ?? 1) < 0.6)
            ? 'Comida registrada con estimación local (Gemini sin cuota o no disponible).'
            : 'Comida registrada con estimación nutricional.';
        session()->flash('status', $note);
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function analyzePhoto(NutritionAiService $ai): void
    {
        $this->validate([
            'photo' => ['required', 'image', 'max:8192'],
            'meal_type' => ['required', 'in:breakfast,lunch,dinner,snack'],
            'eaten_on' => ['required', 'date'],
            'hint' => ['nullable', 'string', 'max:500'],
        ]);

        $user = auth()->user();
        $path = $this->photo->store('meals/'.$user->id, 'public');
        $absolute = Storage::disk('public')->path($path);
        $analysis = $ai->analyzePhoto($user, $absolute, $this->meal_type, $this->hint ?: null);

        $meal = Meal::create([
            'user_id' => $user->id,
            'eaten_on' => $this->eaten_on,
            'meal_type' => $this->meal_type,
            'title' => $analysis['title'] ?? 'Comida (foto)',
            'description' => $analysis['description'] ?? $this->hint,
            'photo_path' => $path,
            'source' => 'photo_ai',
            'calories' => $analysis['calories'] ?? 0,
            'protein_g' => $analysis['protein_g'] ?? 0,
            'carbs_g' => $analysis['carbs_g'] ?? 0,
            'fat_g' => $analysis['fat_g'] ?? 0,
            'fiber_g' => $analysis['fiber_g'] ?? 0,
            'micros' => $analysis['micros'] ?? [],
            'ai_confidence' => $analysis['confidence'] ?? null,
            'confirmed' => false,
        ]);

        foreach (($analysis['items'] ?? []) as $item) {
            $meal->items()->create([
                'name' => $item['name'] ?? 'Item',
                'quantity_g' => $item['quantity_g'] ?? 100,
                'calories' => $item['calories'] ?? 0,
                'protein_g' => $item['protein_g'] ?? 0,
                'carbs_g' => $item['carbs_g'] ?? 0,
                'fat_g' => $item['fat_g'] ?? 0,
            ]);
        }

        $this->pendingMealId = $meal->id;
        $this->preview = [
            'title' => $meal->title,
            'calories' => (float) $meal->calories,
            'protein_g' => (float) $meal->protein_g,
            'carbs_g' => (float) $meal->carbs_g,
            'fat_g' => (float) $meal->fat_g,
            'confidence' => $meal->ai_confidence,
        ];
    }

    public function confirmPhoto(DailySummaryService $summaries): void
    {
        $meal = Meal::query()->where('user_id', auth()->id())->findOrFail($this->pendingMealId);
        $meal->update([
            'title' => $this->preview['title'] ?? $meal->title,
            'calories' => $this->preview['calories'] ?? $meal->calories,
            'protein_g' => $this->preview['protein_g'] ?? $meal->protein_g,
            'carbs_g' => $this->preview['carbs_g'] ?? $meal->carbs_g,
            'fat_g' => $this->preview['fat_g'] ?? $meal->fat_g,
            'confirmed' => true,
        ]);
        $summaries->rebuild(auth()->user(), $meal->eaten_on);
        session()->flash('status', 'Foto confirmada y añadida al seguimiento.');
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.meal-create');
    }
}
