<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailySummary;
use App\Models\Meal;
use App\Services\DailySummaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function today(Request $request, DailySummaryService $summaries): JsonResponse
    {
        $user = $request->user()->load(['profile', 'activeDietAssignment.dietPlan']);
        $date = $request->query('date', now()->toDateString());

        $summary = DailySummary::query()
            ->where('user_id', $user->id)
            ->whereDate('summary_date', $date)
            ->first();

        if (! $summary) {
            $summary = $summaries->rebuild($user, $date);
        }

        $meals = Meal::query()
            ->with('items')
            ->where('user_id', $user->id)
            ->whereDate('eaten_on', $date)
            ->orderBy('created_at')
            ->get();

        $targets = [
            'calories' => $user->profile?->calorie_target,
            'protein_g' => $user->profile?->protein_target_g,
            'carbs_g' => $user->profile?->carbs_target_g,
            'fat_g' => $user->profile?->fat_target_g,
        ];

        return response()->json([
            'date' => $date,
            'profile' => $user->profile,
            'diet' => $user->activeDietAssignment?->dietPlan,
            'summary' => $summary,
            'targets' => $targets,
            'remaining' => [
                'calories' => max(0, ($targets['calories'] ?? 0) - (float) $summary->calories),
                'protein_g' => max(0, ($targets['protein_g'] ?? 0) - (float) $summary->protein_g),
                'carbs_g' => max(0, ($targets['carbs_g'] ?? 0) - (float) $summary->carbs_g),
                'fat_g' => max(0, ($targets['fat_g'] ?? 0) - (float) $summary->fat_g),
            ],
            'meals' => $meals,
            'disclaimer' => 'DietaIA ofrece orientación general y no sustituye consejo médico ni nutricional profesional.',
        ]);
    }
}
