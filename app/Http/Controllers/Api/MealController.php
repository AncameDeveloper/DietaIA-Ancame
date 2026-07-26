<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Services\DailySummaryService;
use App\Services\NutritionAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MealController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $date = $request->query('date', now()->toDateString());

        $meals = Meal::query()
            ->with('items')
            ->where('user_id', $request->user()->id)
            ->whereDate('eaten_on', $date)
            ->orderBy('created_at')
            ->get();

        return response()->json($meals);
    }

    public function store(Request $request, NutritionAiService $ai, DailySummaryService $summaries): JsonResponse
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:2000'],
            'meal_type' => ['nullable', 'in:breakfast,lunch,dinner,snack'],
            'eaten_on' => ['nullable', 'date'],
            'use_ai' => ['nullable', 'boolean'],
            'title' => ['nullable', 'string', 'max:180'],
            'calories' => ['nullable', 'numeric'],
            'protein_g' => ['nullable', 'numeric'],
            'carbs_g' => ['nullable', 'numeric'],
            'fat_g' => ['nullable', 'numeric'],
            'fiber_g' => ['nullable', 'numeric'],
            'micros' => ['nullable', 'array'],
            'confirmed' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $mealType = $data['meal_type'] ?? 'lunch';
        $eatenOn = $data['eaten_on'] ?? now()->toDateString();

        $analysis = null;
        if ($request->boolean('use_ai', true) && empty($data['calories'])) {
            $analysis = $ai->analyzeText($user, $data['description'], $mealType);
        }

        $meal = Meal::create([
            'user_id' => $user->id,
            'eaten_on' => $eatenOn,
            'meal_type' => $mealType,
            'title' => $data['title'] ?? ($analysis['title'] ?? 'Comida'),
            'description' => $data['description'],
            'source' => $analysis ? 'text_ai' : 'manual',
            'calories' => $data['calories'] ?? ($analysis['calories'] ?? 0),
            'protein_g' => $data['protein_g'] ?? ($analysis['protein_g'] ?? 0),
            'carbs_g' => $data['carbs_g'] ?? ($analysis['carbs_g'] ?? 0),
            'fat_g' => $data['fat_g'] ?? ($analysis['fat_g'] ?? 0),
            'fiber_g' => $data['fiber_g'] ?? ($analysis['fiber_g'] ?? 0),
            'micros' => $data['micros'] ?? ($analysis['micros'] ?? []),
            'ai_confidence' => $analysis['confidence'] ?? null,
            'confirmed' => $data['confirmed'] ?? true,
        ]);

        foreach (($analysis['items'] ?? []) as $item) {
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

        return response()->json($meal->load('items'), 201);
    }

    public function analyzePhoto(Request $request, NutritionAiService $ai, DailySummaryService $summaries): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'max:8192'],
            'meal_type' => ['nullable', 'in:breakfast,lunch,dinner,snack'],
            'eaten_on' => ['nullable', 'date'],
            'hint' => ['nullable', 'string', 'max:500'],
            'confirm' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $path = $request->file('photo')->store('meals/'.$user->id, 'public');
        $absolute = Storage::disk('public')->path($path);
        $mealType = $data['meal_type'] ?? 'lunch';
        $eatenOn = $data['eaten_on'] ?? now()->toDateString();

        $analysis = $ai->analyzePhoto($user, $absolute, $mealType, $data['hint'] ?? null);

        $meal = Meal::create([
            'user_id' => $user->id,
            'eaten_on' => $eatenOn,
            'meal_type' => $mealType,
            'title' => $analysis['title'] ?? 'Comida (foto)',
            'description' => $analysis['description'] ?? ($data['hint'] ?? null),
            'photo_path' => $path,
            'source' => 'photo_ai',
            'calories' => $analysis['calories'] ?? 0,
            'protein_g' => $analysis['protein_g'] ?? 0,
            'carbs_g' => $analysis['carbs_g'] ?? 0,
            'fat_g' => $analysis['fat_g'] ?? 0,
            'fiber_g' => $analysis['fiber_g'] ?? 0,
            'micros' => $analysis['micros'] ?? [],
            'ai_confidence' => $analysis['confidence'] ?? null,
            'confirmed' => $request->boolean('confirm', false),
        ]);

        foreach (($analysis['items'] ?? []) as $item) {
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

        if ($meal->confirmed) {
            $summaries->rebuild($user, $eatenOn);
        }

        return response()->json([
            'meal' => $meal->load('items'),
            'analysis' => $analysis,
            'message' => $meal->confirmed
                ? 'Comida registrada'
                : 'Revisa y confirma los valores nutricionales estimados.',
        ], 201);
    }

    public function confirm(Request $request, Meal $meal, DailySummaryService $summaries): JsonResponse
    {
        abort_unless($meal->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:180'],
            'calories' => ['nullable', 'numeric'],
            'protein_g' => ['nullable', 'numeric'],
            'carbs_g' => ['nullable', 'numeric'],
            'fat_g' => ['nullable', 'numeric'],
            'fiber_g' => ['nullable', 'numeric'],
            'micros' => ['nullable', 'array'],
        ]);

        $meal->fill($data);
        $meal->confirmed = true;
        $meal->save();

        $summaries->rebuild($request->user(), $meal->eaten_on);

        return response()->json($meal->load('items'));
    }

    public function destroy(Request $request, Meal $meal, DailySummaryService $summaries): JsonResponse
    {
        abort_unless($meal->user_id === $request->user()->id, 403);
        $date = $meal->eaten_on;
        $meal->delete();
        $summaries->rebuild($request->user(), $date);

        return response()->json(['message' => 'Eliminada']);
    }
}
