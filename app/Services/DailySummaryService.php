<?php

namespace App\Services;

use App\Models\DailySummary;
use App\Models\Meal;
use App\Models\User;
use Carbon\CarbonInterface;

class DailySummaryService
{
    public function rebuild(User $user, CarbonInterface|string $date): DailySummary
    {
        $day = $date instanceof CarbonInterface ? $date->toDateString() : $date;

        $meals = Meal::query()
            ->where('user_id', $user->id)
            ->whereDate('eaten_on', $day)
            ->where('confirmed', true)
            ->get();

        $micros = [];
        foreach ($meals as $meal) {
            foreach (($meal->micros ?? []) as $key => $value) {
                $micros[$key] = ($micros[$key] ?? 0) + (float) $value;
            }
        }

        $existing = DailySummary::query()
            ->where('user_id', $user->id)
            ->whereDate('summary_date', $day)
            ->first();

        return DailySummary::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'summary_date' => $day,
            ],
            [
                'calories' => $meals->sum('calories'),
                'protein_g' => $meals->sum('protein_g'),
                'carbs_g' => $meals->sum('carbs_g'),
                'fat_g' => $meals->sum('fat_g'),
                'fiber_g' => $meals->sum('fiber_g'),
                'water_glasses' => $existing?->water_glasses ?? 0,
                'micros' => $micros,
            ]
        );
    }
}
