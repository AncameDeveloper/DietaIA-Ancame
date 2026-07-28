<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class Labels
{
    /** Formato visible en ES: día/mes/año */
    public static function date(CarbonInterface|string|null $date, string $fallback = '—'): string
    {
        if ($date === null || $date === '') {
            return $fallback;
        }

        try {
            $parsed = $date instanceof CarbonInterface ? $date : Carbon::parse($date);

            return $parsed->format('d/m/Y');
        } catch (\Throwable) {
            return is_string($date) ? $date : $fallback;
        }
    }

    public static function mealType(string $type): string
    {
        return match ($type) {
            'breakfast' => 'Desayuno',
            'lunch' => 'Almuerzo',
            'dinner' => 'Cena',
            'snack' => 'Snack',
            default => 'Comida',
        };
    }

    public static function mealSource(string $source): string
    {
        return match ($source) {
            'text_ai', 'photo_ai' => 'Añadido por IA',
            'menu' => 'Desde sugerencia',
            'manual' => 'Manual',
            default => 'Registrado',
        };
    }

    public static function nutrient(string $key): string
    {
        $map = [
            'vitamin_a_mcg' => 'Vitamina A',
            'vitamin_c_mg' => 'Vitamina C',
            'vitamin_d_mcg' => 'Vitamina D',
            'vitamin_e_mg' => 'Vitamina E',
            'calcium_mg' => 'Calcio',
            'iron_mg' => 'Hierro',
            'magnesium_mg' => 'Magnesio',
            'potassium_mg' => 'Potasio',
            'folate_mcg' => 'Folato',
            'zinc_mg' => 'Zinc',
            'fiber_g' => 'Fibra',
        ];

        if (isset($map[$key])) {
            return $map[$key];
        }

        $clean = str_replace(['_mcg', '_mg', '_g', '_'], ['', '', '', ' '], $key);

        return ucfirst(trim($clean));
    }

    public static function nutrientUnit(string $key): string
    {
        return match (true) {
            str_ends_with($key, '_mcg') => 'µg',
            str_ends_with($key, '_mg') => 'mg',
            str_ends_with($key, '_g') => 'g',
            default => '',
        };
    }

    public static function nutrientDailyTarget(string $key): ?float
    {
        return match ($key) {
            'vitamin_a_mcg' => 800.0,
            'vitamin_c_mg' => 90.0,
            'vitamin_d_mcg' => 15.0,
            'vitamin_e_mg' => 15.0,
            'calcium_mg' => 1000.0,
            'iron_mg' => 14.0,
            'magnesium_mg' => 350.0,
            'potassium_mg' => 3500.0,
            'folate_mcg' => 400.0,
            'zinc_mg' => 11.0,
            'fiber_g' => 25.0,
            default => null,
        };
    }

    public static function mealBlockIcon(string $type): string
    {
        return match ($type) {
            'breakfast' => '🍳',
            'lunch' => '🥗',
            'dinner' => '🐟',
            'snack' => '🍎',
            default => '🍽️',
        };
    }

    public static function goal(string $goal): string
    {
        return match ($goal) {
            'gain_muscle' => 'Ganar músculo',
            'maintain' => 'Mantener peso',
            default => 'Perder peso',
        };
    }
}
