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
        $payload = $ai->tips($user);

        return response()->json([
            'tips' => $payload['tips'] ?? [],
            'disclaimer' => 'DietaIA no sustituye consejo médico ni nutricional profesional.',
        ]);
    }
}
