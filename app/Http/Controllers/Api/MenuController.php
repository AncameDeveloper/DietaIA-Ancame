<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeeklyMenu;
use App\Services\NutritionAiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function generate(Request $request, NutritionAiService $ai): JsonResponse
    {
        $data = $request->validate([
            'horizon' => ['required', 'in:daily,weekly'],
        ]);

        $user = $request->user()->load(['profile', 'activeDietAssignment.dietPlan']);
        $content = $ai->generateMenu($user, $data['horizon']);

        $menu = WeeklyMenu::create([
            'user_id' => $user->id,
            'diet_plan_id' => $user->activeDietAssignment?->diet_plan_id,
            'week_start' => Carbon::now()->startOfWeek()->toDateString(),
            'horizon' => $data['horizon'],
            'content' => $content,
            'notes' => $content['notes'] ?? null,
        ]);

        return response()->json($menu->load('dietPlan'), 201);
    }

    public function latest(Request $request): JsonResponse
    {
        $horizon = $request->query('horizon');

        $query = WeeklyMenu::query()
            ->with('dietPlan')
            ->where('user_id', $request->user()->id)
            ->latest();

        if ($horizon) {
            $query->where('horizon', $horizon);
        }

        return response()->json($query->first());
    }

    public function index(Request $request): JsonResponse
    {
        $menus = WeeklyMenu::query()
            ->with('dietPlan')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->limit(10)
            ->get();

        return response()->json($menus);
    }
}
