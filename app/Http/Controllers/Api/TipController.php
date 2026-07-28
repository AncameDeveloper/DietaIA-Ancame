<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NutritionAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TipController extends Controller
{
    public function index(Request $request, NutritionAiService $ai): JsonResponse
    {
        $user = $request->user()->load(['profile', 'activeDietAssignment.dietPlan']);
        $forceRefresh = $request->boolean('refresh');

        if (! $forceRefresh) {
            $cached = NutritionAiService::cachedTips((int) $user->id);
            if ($cached !== null) {
                return response()->json([
                    'tips' => $cached['tips'] ?? [],
                    'cached' => true,
                    'disclaimer' => 'DietaIA no sustituye consejo médico ni nutricional profesional.',
                ]);
            }

            // No llamar a Gemini al abrir la pantalla: el cliente pide refresh=1.
            return response()->json([
                'tips' => [],
                'cached' => false,
                'disclaimer' => 'DietaIA no sustituye consejo médico ni nutricional profesional.',
            ]);
        }

        $payload = $ai->tips($user, forceRefresh: true);

        return response()->json([
            'tips' => $payload['tips'] ?? [],
            'cached' => false,
            'disclaimer' => 'DietaIA no sustituye consejo médico ni nutricional profesional.',
        ]);
    }
}
