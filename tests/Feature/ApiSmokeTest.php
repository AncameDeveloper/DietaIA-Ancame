<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_login_dashboard_and_meal_flow(): void
    {
        $this->seed();

        $register = $this->postJson('/api/register', [
            'name' => 'Ana',
            'email' => 'ana@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $register->assertCreated()->assertJsonStructure(['token', 'user']);

        $token = $register->json('token');

        $this->withToken($token)
            ->putJson('/api/profile', [
                'age' => 28,
                'sex' => 'female',
                'weight_kg' => 70,
                'height_cm' => 165,
                'activity_level' => 'light',
                'goal' => 'lose_weight',
                'onboarding_completed' => true,
            ])
            ->assertOk();

        $plans = $this->withToken($token)->getJson('/api/diet-plans');
        $plans->assertOk();
        $planId = $plans->json('0.id');

        $this->withToken($token)
            ->postJson('/api/diet-plans/select', ['diet_plan_id' => $planId])
            ->assertOk();

        $meal = $this->withToken($token)->postJson('/api/meals', [
            'description' => 'Pechuga de pollo con brócoli',
            'meal_type' => 'lunch',
            'use_ai' => true,
        ]);
        $meal->assertCreated();
        $mealId = $meal->json('id');

        $this->withToken($token)
            ->getJson('/api/dashboard/today')
            ->assertOk()
            ->assertJsonStructure(['summary', 'targets', 'meals', 'disclaimer']);

        $this->withToken($token)
            ->deleteJson('/api/meals/'.$mealId)
            ->assertOk()
            ->assertJson(['message' => 'Eliminada']);

        $this->withToken($token)
            ->postJson('/api/menus/generate', ['horizon' => 'daily'])
            ->assertCreated();

        $this->withToken($token)
            ->getJson('/api/tips')
            ->assertOk()
            ->assertJsonStructure(['tips', 'disclaimer']);
    }

    public function test_demo_user_can_login(): void
    {
        $this->seed();

        $this->postJson('/api/login', [
            'email' => 'demo@dietaia.test',
            'password' => 'password',
        ])->assertOk()->assertJsonStructure(['token']);
    }
}
