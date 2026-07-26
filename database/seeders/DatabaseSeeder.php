<?php

namespace Database\Seeders;

use App\Models\DietPlan;
use App\Models\Food;
use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DietPlanSeeder::class,
            FoodSeeder::class,
        ]);

        $user = User::query()->updateOrCreate(
            ['email' => 'demo@dietaia.test'],
            [
                'name' => 'Usuario Demo',
                'password' => Hash::make('password'),
            ]
        );

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'age' => 32,
                'sex' => 'male',
                'weight_kg' => 82.4,
                'start_weight_kg' => 88,
                'target_weight_kg' => 78,
                'height_cm' => 178,
                'activity_level' => 'light',
                'goal' => 'lose_weight',
                'calorie_target' => 2000,
                'protein_target_g' => 150,
                'carbs_target_g' => 180,
                'fat_target_g' => 65,
                'restrictions' => [],
                'allergies' => [],
                'onboarding_completed' => true,
            ]
        );

        foreach ([
            [14, 88.0],
            [10, 86.5],
            [7, 85.2],
            [4, 84.0],
            [2, 83.1],
            [0, 82.4],
        ] as [$daysAgo, $weight]) {
            WeightLog::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'logged_on' => now()->subDays($daysAgo)->toDateString(),
                ],
                ['weight_kg' => $weight]
            );
        }
    }
}
