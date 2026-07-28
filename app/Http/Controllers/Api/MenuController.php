<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeeklyMenu;
use App\Services\NutritionAiService;
use App\Services\ShoppingListService;
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

    public function shoppingList(Request $request, ShoppingListService $shoppingList): JsonResponse
    {
        $data = $request->validate([
            'menu_id' => ['nullable', 'integer'],
            'horizon' => ['nullable', 'in:daily,weekly'],
            'content' => ['nullable', 'array'],
            'content.days' => ['nullable', 'array'],
            'days' => ['nullable', 'array'],
        ]);

        $content = null;

        if (! empty($data['content']) && is_array($data['content'])) {
            $content = $data['content'];
        } elseif (! empty($data['days']) && is_array($data['days'])) {
            $content = ['days' => $data['days']];
        } else {
            $query = WeeklyMenu::query()
                ->where('user_id', $request->user()->id);

            if (! empty($data['menu_id'])) {
                $query->where('id', $data['menu_id']);
            } else {
                $query->latest();
                if (! empty($data['horizon'])) {
                    $query->where('horizon', $data['horizon']);
                }
            }

            $menu = $query->first();
            abort_if(! $menu, 404, 'No hay menú disponible para generar la lista de la compra.');
            abort_unless($menu->user_id === $request->user()->id, 403);

            $content = is_array($menu->content) ? $menu->content : [];
            $data['menu_id'] = $menu->id;
            $data['horizon'] = $menu->horizon;
        }

        $items = $shoppingList->buildFromMenuContent($content);

        return response()->json([
            'menu_id' => $data['menu_id'] ?? null,
            'horizon' => $data['horizon'] ?? null,
            'count' => count($items),
            'items' => $items,
        ]);
    }
}
