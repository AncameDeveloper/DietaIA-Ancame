<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NutritionAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickEntryAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalize_meal_type_from_spanish_phrases(): void
    {
        $ai = app(NutritionAiService::class);

        $this->assertSame('breakfast', $ai->normalizeMealType(null, 'Hoy he desayunado un café'));
        $this->assertSame('lunch', $ai->normalizeMealType(null, 'Para comida pollo con arroz'));
        $this->assertSame('dinner', $ai->normalizeMealType(null, 'He cenado ensalada'));
        $this->assertSame('snack', $ai->normalizeMealType(null, 'Merienda con yogur'));
        $this->assertSame('Desayuno', $ai->mealTypeLabel('breakfast'));
    }

    public function test_dashboard_page_loads_for_authenticated_user(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'demo@dietaia.test')->firstOrFail();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Asistente IA', false)
            ->assertSee('fab-ai', false)
            ->assertSee('Calorías restantes', false)
            ->assertSee('Desayuno', false)
            ->assertSee('Almuerzo', false)
            ->assertSee('vasos', false);

        $this->actingAs($user)
            ->get('/progress')
            ->assertOk()
            ->assertSee('Peso actual', false)
            ->assertSee('Registrar peso de hoy', false);
    }

    public function test_build_recent_nutrition_context_includes_three_day_window(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'demo@dietaia.test')->firstOrFail();

        $user->meals()->create([
            'eaten_on' => now()->subDay()->toDateString(),
            'meal_type' => 'lunch',
            'title' => 'Pollo con arroz',
            'source' => 'manual',
            'calories' => 500,
            'protein_g' => 40,
            'carbs_g' => 45,
            'fat_g' => 12,
            'fiber_g' => 4,
            'micros' => ['iron_mg' => 2, 'vitamin_c_mg' => 10],
            'confirmed' => true,
        ]);

        $context = app(NutritionAiService::class)->buildRecentNutritionContext($user);

        $this->assertArrayHasKey('history_3_days', $context);
        $this->assertArrayHasKey('likely_gaps', $context);
        $this->assertSame('lose_weight', $context['goal']);
        $this->assertNotEmpty($context['history_3_days']);
    }
}
