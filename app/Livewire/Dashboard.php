<?php

namespace App\Livewire;

use App\Models\Meal;
use App\Services\DailySummaryService;
use App\Services\NutritionAiService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Hoy — DietaIA')]
class Dashboard extends Component
{
    use WithFileUploads;

    public string $date;

    public bool $showQuickAssistant = false;

    /** @var string register|suggest */
    public string $assistantMode = 'register';

    public string $quickText = '';

    public $quickPhoto = null;

    public ?array $quickPreview = null;

    public string $suggestText = '';

    public ?array $suggestResult = null;

    public string $quickError = '';

    public string $quickStatus = '';

    public bool $quickLoading = false;

    public int $waterGlasses = 0;

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->loadWaterGlasses();
    }

    public function updatedDate(): void
    {
        $this->loadWaterGlasses();
    }

    public function addWaterGlass(): void
    {
        $this->waterGlasses = min(20, $this->waterGlasses + 1);
        $this->persistWaterGlasses();
    }

    public function removeWaterGlass(): void
    {
        $this->waterGlasses = max(0, $this->waterGlasses - 1);
        $this->persistWaterGlasses();
    }

    private function loadWaterGlasses(): void
    {
        $summary = \App\Models\DailySummary::query()
            ->where('user_id', auth()->id())
            ->whereDate('summary_date', $this->date)
            ->first();

        $this->waterGlasses = (int) ($summary?->water_glasses ?? 0);
    }

    private function persistWaterGlasses(): void
    {
        $summary = \App\Models\DailySummary::query()->firstOrCreate(
            [
                'user_id' => auth()->id(),
                'summary_date' => $this->date,
            ],
            [
                'calories' => 0,
                'protein_g' => 0,
                'carbs_g' => 0,
                'fat_g' => 0,
                'fiber_g' => 0,
                'water_glasses' => 0,
                'micros' => [],
            ]
        );

        $summary->update(['water_glasses' => $this->waterGlasses]);
    }

    public function openQuickAssistant(?string $mode = null): void
    {
        $this->resetQuickAssistant(keepOpen: true);
        if (in_array($mode, ['register', 'suggest'], true)) {
            $this->assistantMode = $mode;
        }
        $this->showQuickAssistant = true;
    }

    public function setAssistantMode(string $mode): void
    {
        if (! in_array($mode, ['register', 'suggest'], true)) {
            return;
        }

        $this->assistantMode = $mode;
        $this->quickError = '';
        $this->quickStatus = '';
        $this->quickPreview = null;
        $this->suggestResult = null;
        $this->quickPhoto = null;
    }

    public function closeQuickAssistant(): void
    {
        $this->resetQuickAssistant();
    }

    public function analyzeQuickEntry(NutritionAiService $ai): void
    {
        $this->quickError = '';
        $this->quickStatus = '';
        $this->quickPreview = null;

        $this->validate([
            'quickText' => ['nullable', 'string', 'max:2000'],
            'quickPhoto' => ['nullable', 'image', 'max:8192'],
        ]);

        if (blank($this->quickText) && ! $this->quickPhoto) {
            $this->quickError = 'Escribe qué has comido o sube una foto del plato.';

            return;
        }

        $this->quickLoading = true;

        try {
            $user = auth()->user();
            $absolute = null;
            $photoPath = null;

            if ($this->quickPhoto) {
                $photoPath = $this->quickPhoto->store('meals/'.$user->id, 'public');
                $absolute = Storage::disk('public')->path($photoPath);
            }

            $analysis = $ai->analyzeQuickEntry(
                $user,
                filled($this->quickText) ? $this->quickText : null,
                $absolute
            );

            $this->quickPreview = [
                'meal_type' => $analysis['meal_type'] ?? 'lunch',
                'meal_type_label' => $analysis['meal_type_label'] ?? $ai->mealTypeLabel($analysis['meal_type'] ?? 'lunch'),
                'title' => $analysis['title'] ?? 'Comida',
                'description' => $analysis['description'] ?? $this->quickText,
                'items' => $analysis['items'] ?? [],
                'calories' => (float) ($analysis['calories'] ?? 0),
                'protein_g' => (float) ($analysis['protein_g'] ?? 0),
                'carbs_g' => (float) ($analysis['carbs_g'] ?? 0),
                'fat_g' => (float) ($analysis['fat_g'] ?? 0),
                'fiber_g' => (float) ($analysis['fiber_g'] ?? 0),
                'micros' => $analysis['micros'] ?? [],
                'confidence' => $analysis['confidence'] ?? null,
                'photo_path' => $photoPath,
                'source' => $photoPath ? 'photo_ai' : 'text_ai',
            ];
        } catch (\Throwable $e) {
            $this->quickError = 'No se pudo analizar la entrada. Inténtalo de nuevo.';
        } finally {
            $this->quickLoading = false;
        }
    }

    public function confirmQuickEntry(DailySummaryService $summaries): void
    {
        if (! $this->quickPreview) {
            $this->quickError = 'Primero analiza la comida con IA.';

            return;
        }

        $this->persistMealPayload($this->quickPreview, $this->date, $summaries, $this->quickText);
        $this->resetQuickAssistant();
        session()->flash('status', 'Comida añadida con el asistente nutricional.');
    }

    public function requestSuggestions(NutritionAiService $ai): void
    {
        $this->quickError = '';
        $this->quickStatus = '';
        $this->suggestResult = null;

        $data = $this->validate([
            'suggestText' => ['required', 'string', 'max:2000'],
        ], [], [
            'suggestText' => 'petición',
        ]);

        $this->quickLoading = true;

        try {
            $this->suggestResult = $ai->suggestBalancedMeals(auth()->user(), $data['suggestText']);
            if (empty($this->suggestResult['suggestions'])) {
                $this->quickError = 'La IA no devolvió sugerencias. Prueba a reformular la petición.';
            }
        } catch (\Throwable $e) {
            $this->quickError = 'No se pudieron generar sugerencias. Inténtalo de nuevo.';
        } finally {
            $this->quickLoading = false;
        }
    }

    public function acceptSuggestion(string $suggestionId, DailySummaryService $summaries): void
    {
        $suggestions = collect($this->suggestResult['suggestions'] ?? []);
        $suggestion = $suggestions->firstWhere('id', $suggestionId);

        if (! $suggestion) {
            $this->quickError = 'No se encontró la sugerencia seleccionada.';

            return;
        }

        $targetDate = $suggestion['target_date'] ?? $this->date;
        $payload = [
            'meal_type' => $suggestion['meal_type'] ?? 'lunch',
            'title' => $suggestion['title'] ?? 'Sugerencia IA',
            'description' => $suggestion['description'] ?? ($suggestion['reason'] ?? ''),
            'items' => $suggestion['items'] ?? [],
            'calories' => $suggestion['calories'] ?? 0,
            'protein_g' => $suggestion['protein_g'] ?? 0,
            'carbs_g' => $suggestion['carbs_g'] ?? 0,
            'fat_g' => $suggestion['fat_g'] ?? 0,
            'fiber_g' => $suggestion['fiber_g'] ?? 0,
            'micros' => $suggestion['micros'] ?? [],
            'confidence' => null,
            'photo_path' => null,
            'source' => 'menu',
        ];

        $this->persistMealPayload($payload, $targetDate, $summaries);
        $this->date = $targetDate;
        $this->suggestResult['suggestions'] = $suggestions
            ->reject(fn ($item) => ($item['id'] ?? null) === $suggestionId)
            ->values()
            ->all();
        $this->quickStatus = 'Insertada en el plan del '.$targetDate.'.';
        session()->flash('status', 'Sugerencia insertada en tu plan del '.$targetDate.'.');
    }

    private function persistMealPayload(array $preview, string $eatenOn, DailySummaryService $summaries, ?string $fallbackDescription = null): void
    {
        $user = auth()->user();

        $meal = Meal::create([
            'user_id' => $user->id,
            'eaten_on' => $eatenOn,
            'meal_type' => $preview['meal_type'] ?? 'lunch',
            'title' => $preview['title'] ?? 'Comida',
            'description' => $preview['description'] ?? $fallbackDescription,
            'photo_path' => $preview['photo_path'] ?? null,
            'source' => $preview['source'] ?? 'text_ai',
            'calories' => $preview['calories'] ?? 0,
            'protein_g' => $preview['protein_g'] ?? 0,
            'carbs_g' => $preview['carbs_g'] ?? 0,
            'fat_g' => $preview['fat_g'] ?? 0,
            'fiber_g' => $preview['fiber_g'] ?? 0,
            'micros' => $preview['micros'] ?? [],
            'ai_confidence' => $preview['confidence'] ?? null,
            'confirmed' => true,
        ]);

        foreach (($preview['items'] ?? []) as $item) {
            $meal->items()->create([
                'name' => $item['name'] ?? 'Item',
                'quantity_g' => $item['quantity_g'] ?? 100,
                'calories' => $item['calories'] ?? 0,
                'protein_g' => $item['protein_g'] ?? 0,
                'carbs_g' => $item['carbs_g'] ?? 0,
                'fat_g' => $item['fat_g'] ?? 0,
                'micros' => $item['micros'] ?? null,
            ]);
        }

        $summaries->rebuild($user, $eatenOn);
    }

    private function resetQuickAssistant(bool $keepOpen = false): void
    {
        $this->showQuickAssistant = $keepOpen;
        $this->assistantMode = 'register';
        $this->quickText = '';
        $this->suggestText = '';
        $this->quickPhoto = null;
        $this->quickPreview = null;
        $this->suggestResult = null;
        $this->quickError = '';
        $this->quickStatus = '';
        $this->quickLoading = false;
        $this->resetValidation();
    }

    public function render(DailySummaryService $summaries, NutritionAiService $ai)
    {
        $user = auth()->user()->load(['profile', 'activeDietAssignment.dietPlan']);
        $summary = $summaries->rebuild($user, $this->date);
        $meals = Meal::query()
            ->where('user_id', $user->id)
            ->whereDate('eaten_on', $this->date)
            ->orderBy('created_at')
            ->get();

        $mealsByType = [
            'breakfast' => $meals->where('meal_type', 'breakfast')->values(),
            'lunch' => $meals->where('meal_type', 'lunch')->values(),
            'dinner' => $meals->where('meal_type', 'dinner')->values(),
            'snack' => $meals->where('meal_type', 'snack')->values(),
        ];

        $targets = [
            'calories' => (float) ($user->profile?->calorie_target ?? 0),
            'protein_g' => (float) ($user->profile?->protein_target_g ?? 0),
            'carbs_g' => (float) ($user->profile?->carbs_target_g ?? 0),
            'fat_g' => (float) ($user->profile?->fat_target_g ?? 0),
        ];

        $nutritionContext = $this->showQuickAssistant
            ? $ai->buildRecentNutritionContext($user)
            : null;

        return view('livewire.dashboard', compact('user', 'summary', 'meals', 'mealsByType', 'targets', 'nutritionContext'));
    }
}
