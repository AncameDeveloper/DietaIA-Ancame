<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NutritionAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function nutritionistContext(Request $request, NutritionAiService $ai): JsonResponse
    {
        $user = $request->user()->load(['profile', 'activeDietAssignment.dietPlan']);

        return response()->json([
            'context' => $ai->publicNutritionistContext(null, $user),
            'disclaimer' => 'DietaIA ofrece orientación general y no sustituye consejo médico ni nutricional profesional.',
        ]);
    }

    public function nutritionistChat(Request $request, NutritionAiService $ai): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'min:3', 'max:2000'],
            'history' => ['nullable', 'array', 'max:12'],
            'history.*.role' => ['nullable', 'string', 'in:user,assistant'],
            'history.*.content' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user()->load(['profile', 'activeDietAssignment.dietPlan']);
        $payload = $ai->nutritionistChat(
            $user,
            (string) $data['message'],
            $data['history'] ?? [],
        );

        return response()->json($payload);
    }

    public function mealSuggestions(Request $request, NutritionAiService $ai): JsonResponse
    {
        $data = $request->validate([
            'request' => ['required', 'string', 'min:3', 'max:2000'],
            'history' => ['nullable', 'array', 'max:12'],
            'history.*.role' => ['nullable', 'string', 'in:user,assistant'],
            'history.*.content' => ['nullable', 'string', 'max:2000'],
        ]);

        $user = $request->user()->load(['profile', 'activeDietAssignment.dietPlan']);
        $payload = $ai->suggestBalancedMeals(
            $user,
            (string) $data['request'],
            $data['history'] ?? [],
        );

        return response()->json([
            'summary' => $payload['summary'] ?? null,
            'nutrient_focus' => $payload['nutrient_focus'] ?? [],
            'suggestions' => $payload['suggestions'] ?? [],
            'context' => $ai->publicNutritionistContext(null, $user),
            'disclaimer' => 'DietaIA ofrece orientación general y no sustituye consejo médico ni nutricional profesional.',
        ]);
    }
}