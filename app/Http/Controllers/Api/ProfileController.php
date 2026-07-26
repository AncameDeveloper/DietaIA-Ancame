<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalorieCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['profile', 'activeDietAssignment.dietPlan']);

        return response()->json($user);
    }

    public function update(Request $request, CalorieCalculatorService $calories): JsonResponse
    {
        $data = $request->validate([
            'age' => ['nullable', 'integer', 'min:12', 'max:100'],
            'sex' => ['nullable', 'in:male,female,other'],
            'weight_kg' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'height_cm' => ['nullable', 'numeric', 'min:100', 'max:250'],
            'activity_level' => ['nullable', 'in:sedentary,light,moderate,active,very_active'],
            'goal' => ['nullable', 'in:lose_weight,maintain,gain_muscle'],
            'restrictions' => ['nullable', 'array'],
            'allergies' => ['nullable', 'array'],
            'onboarding_completed' => ['nullable', 'boolean'],
            'name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = $request->user();
        if (! empty($data['name'])) {
            $user->update(['name' => $data['name']]);
        }

        $profile = $user->profile()->firstOrCreate([]);
        $profile->fill(collect($data)->except('name')->all());

        $macros = $user->activeDietAssignment?->dietPlan?->macros_ratio;
        $calories->applyToProfile($profile, $macros);
        $profile->refresh();

        if ($request->boolean('onboarding_completed')) {
            $profile->update(['onboarding_completed' => true]);
        }

        return response()->json($user->fresh()->load(['profile', 'activeDietAssignment.dietPlan']));
    }
}
