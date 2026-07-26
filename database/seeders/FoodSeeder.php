<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Seeder;

class FoodSeeder extends Seeder
{
    public function run(): void
    {
        $foods = [
            ['name' => 'Pechuga de pollo', 'category' => 'proteina', 'calories' => 165, 'protein_g' => 31, 'carbs_g' => 0, 'fat_g' => 3.6, 'fiber_g' => 0, 'micros' => ['iron_mg' => 1, 'potassium_mg' => 256]],
            ['name' => 'Salmón', 'category' => 'proteina', 'calories' => 208, 'protein_g' => 20, 'carbs_g' => 0, 'fat_g' => 13, 'fiber_g' => 0, 'micros' => ['vitamin_d_mcg' => 11, 'potassium_mg' => 363]],
            ['name' => 'Huevo', 'category' => 'proteina', 'calories' => 155, 'protein_g' => 13, 'carbs_g' => 1.1, 'fat_g' => 11, 'fiber_g' => 0, 'micros' => ['vitamin_a_mcg' => 160, 'vitamin_d_mcg' => 2]],
            ['name' => 'Yogur griego', 'category' => 'lacteo', 'calories' => 97, 'protein_g' => 9, 'carbs_g' => 3.6, 'fat_g' => 5, 'fiber_g' => 0, 'micros' => ['calcium_mg' => 110]],
            ['name' => 'Quinoa cocida', 'category' => 'cereal', 'calories' => 120, 'protein_g' => 4.4, 'carbs_g' => 21, 'fat_g' => 1.9, 'fiber_g' => 2.8, 'micros' => ['magnesium_mg' => 64, 'iron_mg' => 1.5]],
            ['name' => 'Arroz integral cocido', 'category' => 'cereal', 'calories' => 111, 'protein_g' => 2.6, 'carbs_g' => 23, 'fat_g' => 0.9, 'fiber_g' => 1.8, 'micros' => ['magnesium_mg' => 43]],
            ['name' => 'Avena', 'category' => 'cereal', 'calories' => 389, 'protein_g' => 17, 'carbs_g' => 66, 'fat_g' => 7, 'fiber_g' => 10, 'micros' => ['iron_mg' => 4.7, 'magnesium_mg' => 177]],
            ['name' => 'Brócoli', 'category' => 'verdura', 'calories' => 34, 'protein_g' => 2.8, 'carbs_g' => 7, 'fat_g' => 0.4, 'fiber_g' => 2.6, 'micros' => ['vitamin_c_mg' => 89, 'vitamin_a_mcg' => 31]],
            ['name' => 'Espinacas', 'category' => 'verdura', 'calories' => 23, 'protein_g' => 2.9, 'carbs_g' => 3.6, 'fat_g' => 0.4, 'fiber_g' => 2.2, 'micros' => ['iron_mg' => 2.7, 'vitamin_a_mcg' => 469]],
            ['name' => 'Aguacate', 'category' => 'fruta', 'calories' => 160, 'protein_g' => 2, 'carbs_g' => 8.5, 'fat_g' => 15, 'fiber_g' => 6.7, 'micros' => ['potassium_mg' => 485, 'magnesium_mg' => 29]],
            ['name' => 'Manzana', 'category' => 'fruta', 'calories' => 52, 'protein_g' => 0.3, 'carbs_g' => 14, 'fat_g' => 0.2, 'fiber_g' => 2.4, 'micros' => ['vitamin_c_mg' => 4.6, 'potassium_mg' => 107]],
            ['name' => 'Plátano', 'category' => 'fruta', 'calories' => 89, 'protein_g' => 1.1, 'carbs_g' => 23, 'fat_g' => 0.3, 'fiber_g' => 2.6, 'micros' => ['potassium_mg' => 358, 'vitamin_c_mg' => 8.7]],
            ['name' => 'Almendras', 'category' => 'frutos_secos', 'calories' => 579, 'protein_g' => 21, 'carbs_g' => 22, 'fat_g' => 50, 'fiber_g' => 12, 'micros' => ['magnesium_mg' => 270, 'calcium_mg' => 269]],
            ['name' => 'Aceite de oliva', 'category' => 'grasa', 'calories' => 884, 'protein_g' => 0, 'carbs_g' => 0, 'fat_g' => 100, 'fiber_g' => 0, 'micros' => ['vitamin_e_mg' => 14]],
            ['name' => 'Lentejas cocidas', 'category' => 'legumbre', 'calories' => 116, 'protein_g' => 9, 'carbs_g' => 20, 'fat_g' => 0.4, 'fiber_g' => 7.9, 'micros' => ['iron_mg' => 3.3, 'folate_mcg' => 181]],
            ['name' => 'Tofu', 'category' => 'proteina', 'calories' => 76, 'protein_g' => 8, 'carbs_g' => 1.9, 'fat_g' => 4.8, 'fiber_g' => 0.3, 'micros' => ['calcium_mg' => 350, 'iron_mg' => 5.4]],
            ['name' => 'Atún en lata', 'category' => 'proteina', 'calories' => 116, 'protein_g' => 26, 'carbs_g' => 0, 'fat_g' => 0.8, 'fiber_g' => 0, 'micros' => ['vitamin_d_mcg' => 1.7]],
            ['name' => 'Patata cocida', 'category' => 'tubérculo', 'calories' => 87, 'protein_g' => 1.9, 'carbs_g' => 20, 'fat_g' => 0.1, 'fiber_g' => 1.8, 'micros' => ['potassium_mg' => 379, 'vitamin_c_mg' => 13]],
        ];

        foreach ($foods as $food) {
            Food::query()->updateOrCreate(
                ['name' => $food['name']],
                $food + ['serving_g' => 100]
            );
        }
    }
}
