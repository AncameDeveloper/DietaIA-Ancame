<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DietPlan;
use App\Models\UserDietAssignment;
use App\Services\CalorieCalculatorService;
use App\Services\NutritionAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DietPlanController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            DietPlan::query()->where('is_active', true)->orderBy('name')->get()
        );
    }

    public function select(Request $request, CalorieCalculatorService $calories): JsonResponse
    {
        $data = $request->validate([
            'diet_plan_id' => ['required', 'exists:diet_plans,id'],
        ]);

        $user = $request->user();
        $user->dietAssignments()->where('is_active', true)->update(['is_active' => false]);

        $assignment = UserDietAssignment::create([
            'user_id' => $user->id,
            'diet_plan_id' => $data['diet_plan_id'],
            'is_active' => true,
            'source' => 'manual',
            'started_at' => now(),
        ]);

        $plan = DietPlan::findOrFail($data['diet_plan_id']);
        if ($user->profile) {
            $calories->applyToProfile($user->profile, $plan->macros_ratio);
        }

        return response()->json($assignment->load('dietPlan'));
    }

    public function suggest(Request $request, NutritionAiService $ai, CalorieCalculatorService $calories): JsonResponse
    {
        $user = $request->user()->load('profile');
        $suggestion = $ai->suggestDiet($user);
        $plan = $suggestion['recommended'];

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
        }

        return response()->json([
            'recommended' => $plan,
            'reason' => $suggestion['reason'],
            'alternatives' => $suggestion['alternatives'],
            'assignment' => $user->fresh()->activeDietAssignment?->load('dietPlan'),
        ]);
    }
}
