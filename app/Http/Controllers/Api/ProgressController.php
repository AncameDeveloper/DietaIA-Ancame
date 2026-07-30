<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Livewire\ProgressPage;
use App\Services\WeightLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressController extends Controller
{
    public function weight(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 90);
        $days = max(7, min(365, $days));

        $series = ProgressPage::weightHistoryForUser((int) $request->user()->id, $days);
        $weights = array_column($series, 'weight');
        $min = $weights ? min($weights) - 2 : 0;
        $max = $weights ? max($weights) + 2 : 0;
        if ($weights && $max <= $min) {
            $max = $min + 4;
        }

        return response()->json([
            'days' => $days,
            'count' => count($series),
            'min' => round($min, 1),
            'max' => round($max, 1),
            'items' => array_map(fn ($p) => [
                'date' => $p['date'],
                'weight' => $p['weight'],
            ], $series),
        ]);
    }

    public function storeWeight(Request $request, WeightLogService $weights): JsonResponse
    {
        $data = $request->validate([
            'weight' => ['required', 'numeric', 'min:30', 'max:300'],
            'date' => ['nullable', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
            'start_weight' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'target_weight' => ['nullable', 'numeric', 'min:30', 'max:300'],
        ]);

        $log = $weights->upsert(
            $request->user(),
            (float) $data['weight'],
            isset($data['date']) ? (string) $data['date'] : null,
            $data['note'] ?? null,
            isset($data['start_weight']) ? (float) $data['start_weight'] : null,
            isset($data['target_weight']) ? (float) $data['target_weight'] : null,
        );

        $series = ProgressPage::weightHistoryForUser((int) $request->user()->id, 90);

        return response()->json([
            'message' => 'Peso registrado.',
            'log' => [
                'date' => $log->logged_on->toDateString(),
                'weight' => (float) $log->weight_kg,
                'note' => $log->note,
            ],
            'items' => array_map(fn ($p) => [
                'date' => $p['date'],
                'weight' => $p['weight'],
            ], $series),
        ], 201);
    }
}
