<?php

namespace App\Services;

use App\Models\Profile;

class CalorieCalculatorService
{
    private const ACTIVITY_FACTORS = [
        'sedentary' => 1.2,
        'light' => 1.375,
        'moderate' => 1.55,
        'active' => 1.725,
        'very_active' => 1.9,
    ];

    /**
     * @return array{bmr:int,tdee:int,calorie_target:int,protein_target_g:int,carbs_target_g:int,fat_target_g:int}
     */
    public function calculate(Profile $profile, ?array $macrosRatio = null): array
    {
        $weight = (float) ($profile->weight_kg ?? 70);
        $height = (float) ($profile->height_cm ?? 170);
        $age = (int) ($profile->age ?? 30);
        $sex = $profile->sex ?? 'other';

        // Mifflin-St Jeor
        $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age);
        $bmr += match ($sex) {
            'male' => 5,
            'female' => -161,
            default => -78,
        };

        $factor = self::ACTIVITY_FACTORS[$profile->activity_level ?? 'sedentary'] ?? 1.2;
        $tdee = (int) round($bmr * $factor);

        $calorieTarget = match ($profile->goal ?? 'lose_weight') {
            'lose_weight' => max(1200, $tdee - 500),
            'gain_muscle' => $tdee + 300,
            default => $tdee,
        };

        $ratio = $macrosRatio ?? [
            'protein' => 0.30,
            'carbs' => 0.40,
            'fat' => 0.30,
        ];

        $protein = (int) round(($calorieTarget * ($ratio['protein'] ?? 0.30)) / 4);
        $carbs = (int) round(($calorieTarget * ($ratio['carbs'] ?? 0.40)) / 4);
        $fat = (int) round(($calorieTarget * ($ratio['fat'] ?? 0.30)) / 9);

        return [
            'bmr' => (int) round($bmr),
            'tdee' => $tdee,
            'calorie_target' => $calorieTarget,
            'protein_target_g' => $protein,
            'carbs_target_g' => $carbs,
            'fat_target_g' => $fat,
        ];
    }

    public function applyToProfile(Profile $profile, ?array $macrosRatio = null): Profile
    {
        $targets = $this->calculate($profile, $macrosRatio);
        $profile->fill([
            'calorie_target' => $targets['calorie_target'],
            'protein_target_g' => $targets['protein_target_g'],
            'carbs_target_g' => $targets['carbs_target_g'],
            'fat_target_g' => $targets['fat_target_g'],
        ]);
        $profile->save();

        return $profile;
    }
}
