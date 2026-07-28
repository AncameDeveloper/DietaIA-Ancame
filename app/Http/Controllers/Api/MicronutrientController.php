<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MicronutrientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MicronutrientController extends Controller
{
    public function index(Request $request, MicronutrientService $micros): JsonResponse
    {
        $range = (string) $request->query('range', '7days');
        $group = (string) $request->query('group', 'all');
        $date = $request->query('date');

        $payload = $micros->forUser(
            $request->user(),
            $range,
            is_string($date) && $date !== '' ? $date : null,
            $group
        );

        return response()->json($payload);
    }
}
