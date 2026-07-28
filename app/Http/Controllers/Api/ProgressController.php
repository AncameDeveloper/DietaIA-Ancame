<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Livewire\ProgressPage;
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
}
