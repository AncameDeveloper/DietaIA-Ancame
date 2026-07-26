<?php

namespace Database\Seeders;

use App\Models\DietPlan;
use Illuminate\Database\Seeder;

class DietPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'keto',
                'name' => 'Keto',
                'description' => 'Muy baja en carbohidratos y alta en grasas saludables para favorecer cetosis.',
                'macros_ratio' => ['protein' => 0.25, 'carbs' => 0.05, 'fat' => 0.70],
                'rules' => ['max_net_carbs_g' => 30, 'prefer' => ['aguacate', 'huevos', 'pescado', 'aceite de oliva']],
            ],
            [
                'slug' => 'ayuno-intermitente',
                'name' => 'Ayuno intermitente',
                'description' => 'Ventana de alimentación (p. ej. 16:8) con comidas densas en nutrientes.',
                'macros_ratio' => ['protein' => 0.30, 'carbs' => 0.35, 'fat' => 0.35],
                'rules' => ['feeding_window' => '16:8', 'meal_count' => 2],
            ],
            [
                'slug' => 'deficit-calorico',
                'name' => 'Baja en calorías',
                'description' => 'Déficit calórico moderado (~500 kcal) con macronutrientes equilibrados.',
                'macros_ratio' => ['protein' => 0.30, 'carbs' => 0.40, 'fat' => 0.30],
                'rules' => ['deficit_kcal' => 500],
            ],
            [
                'slug' => 'mediterranea',
                'name' => 'Mediterránea',
                'description' => 'Patrón basado en verduras, legumbres, pescado, aceite de oliva y cereales integrales.',
                'macros_ratio' => ['protein' => 0.25, 'carbs' => 0.45, 'fat' => 0.30],
                'rules' => ['prefer' => ['aceite de oliva', 'pescado', 'legumbres', 'frutos secos']],
            ],
            [
                'slug' => 'alta-proteina',
                'name' => 'Alta en proteína',
                'description' => 'Enfocada en preservar masa muscular durante la pérdida de peso.',
                'macros_ratio' => ['protein' => 0.40, 'carbs' => 0.30, 'fat' => 0.30],
                'rules' => ['protein_per_kg' => 1.8],
            ],
        ];

        foreach ($plans as $plan) {
            DietPlan::query()->updateOrCreate(['slug' => $plan['slug']], $plan + ['is_active' => true]);
        }
    }
}
