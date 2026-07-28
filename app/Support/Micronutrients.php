<?php

namespace App\Support;

/**
 * Catálogo canónico de micronutrientes + CDR (valores de referencia diarios adultos).
 */
class Micronutrients
{
    public const INFO_MESSAGE = 'Muchas vitaminas y minerales se almacenan en tu organismo. Lo relevante para tu salud no es cumplir el 100% cada día de forma estricta, sino mantener un promedio semanal equilibrado.';

    /**
     * @return list<array{key: string, label: string, group: string, target: float, unit: string}>
     */
    public static function catalog(): array
    {
        return [
            // Complejo B
            ['key' => 'vitamin_b1_mg', 'label' => 'B1 (Tiamina)', 'group' => 'b_vitamins', 'target' => 1.2, 'unit' => 'mg'],
            ['key' => 'vitamin_b2_mg', 'label' => 'B2 (Riboflavina)', 'group' => 'b_vitamins', 'target' => 1.3, 'unit' => 'mg'],
            ['key' => 'vitamin_b3_mg', 'label' => 'B3 (Niacina)', 'group' => 'b_vitamins', 'target' => 16.0, 'unit' => 'mg'],
            ['key' => 'vitamin_b6_mg', 'label' => 'B6 (Piridoxina)', 'group' => 'b_vitamins', 'target' => 1.3, 'unit' => 'mg'],
            ['key' => 'vitamin_b9_mcg', 'label' => 'B9 (Ácido fólico)', 'group' => 'b_vitamins', 'target' => 400.0, 'unit' => 'µg'],
            ['key' => 'vitamin_b12_mcg', 'label' => 'B12 (Cobalamina)', 'group' => 'b_vitamins', 'target' => 2.4, 'unit' => 'µg'],
            // Otras vitaminas
            ['key' => 'vitamin_a_mcg', 'label' => 'Vitamina A', 'group' => 'other_vitamins', 'target' => 800.0, 'unit' => 'µg'],
            ['key' => 'vitamin_c_mg', 'label' => 'Vitamina C', 'group' => 'other_vitamins', 'target' => 90.0, 'unit' => 'mg'],
            ['key' => 'vitamin_d_mcg', 'label' => 'Vitamina D', 'group' => 'other_vitamins', 'target' => 15.0, 'unit' => 'µg'],
            ['key' => 'vitamin_e_mg', 'label' => 'Vitamina E', 'group' => 'other_vitamins', 'target' => 15.0, 'unit' => 'mg'],
            ['key' => 'vitamin_k_mcg', 'label' => 'Vitamina K', 'group' => 'other_vitamins', 'target' => 90.0, 'unit' => 'µg'],
            // Minerales
            ['key' => 'iron_mg', 'label' => 'Hierro', 'group' => 'minerals', 'target' => 14.0, 'unit' => 'mg'],
            ['key' => 'calcium_mg', 'label' => 'Calcio', 'group' => 'minerals', 'target' => 1000.0, 'unit' => 'mg'],
            ['key' => 'magnesium_mg', 'label' => 'Magnesio', 'group' => 'minerals', 'target' => 350.0, 'unit' => 'mg'],
            ['key' => 'potassium_mg', 'label' => 'Potasio', 'group' => 'minerals', 'target' => 3500.0, 'unit' => 'mg'],
            ['key' => 'zinc_mg', 'label' => 'Zinc', 'group' => 'minerals', 'target' => 11.0, 'unit' => 'mg'],
            ['key' => 'sodium_mg', 'label' => 'Sodio', 'group' => 'minerals', 'target' => 2000.0, 'unit' => 'mg'],
            ['key' => 'phosphorus_mg', 'label' => 'Fósforo', 'group' => 'minerals', 'target' => 700.0, 'unit' => 'mg'],
        ];
    }

    /** @return list<string> */
    public static function keys(): array
    {
        return array_column(self::catalog(), 'key');
    }

    /** @return array<string, float> */
    public static function dailyTargets(): array
    {
        $out = [];
        foreach (self::catalog() as $row) {
            $out[$row['key']] = $row['target'];
        }

        return $out;
    }

    /** @return array<string, string> */
    public static function groupLabels(): array
    {
        return [
            'all' => 'Todos',
            'b_vitamins' => 'Vitaminas B',
            'other_vitamins' => 'Otras Vitaminas',
            'minerals' => 'Minerales',
        ];
    }

    /**
     * Esquema JSON para prompts de IA (todas las claves canónicas).
     */
    public static function aiSchemaObject(): string
    {
        $parts = [];
        foreach (self::keys() as $key) {
            $parts[] = "\"{$key}\":number";
        }

        return '{'.implode(',', $parts).'}';
    }

    /**
     * Normaliza aliases → claves canónicas y rellena ceros del catálogo.
     *
     * @param  array<string, mixed>|null  $raw
     * @return array<string, float>
     */
    public static function normalize(?array $raw): array
    {
        $aliases = [
            'folate_mcg' => 'vitamin_b9_mcg',
            'folic_acid_mcg' => 'vitamin_b9_mcg',
            'thiamin_mg' => 'vitamin_b1_mg',
            'thiamine_mg' => 'vitamin_b1_mg',
            'riboflavin_mg' => 'vitamin_b2_mg',
            'niacin_mg' => 'vitamin_b3_mg',
            'pyridoxine_mg' => 'vitamin_b6_mg',
            'cobalamin_mcg' => 'vitamin_b12_mcg',
            'vit_a_mcg' => 'vitamin_a_mcg',
            'vit_c_mg' => 'vitamin_c_mg',
            'vit_d_mcg' => 'vitamin_d_mcg',
            'vit_e_mg' => 'vitamin_e_mg',
            'vit_k_mcg' => 'vitamin_k_mcg',
        ];

        $canonical = array_fill_keys(self::keys(), 0.0);

        foreach (($raw ?? []) as $key => $value) {
            $key = (string) $key;
            $mapped = $aliases[$key] ?? $key;
            if (! array_key_exists($mapped, $canonical)) {
                continue;
            }
            $canonical[$mapped] = round((float) $value, 2);
        }

        return $canonical;
    }

    /**
     * Suma dos mapas de micros (solo claves canónicas).
     *
     * @param  array<string, float>  $a
     * @param  array<string, float|mixed>  $b
     * @return array<string, float>
     */
    public static function sum(array $a, array $b): array
    {
        $out = self::normalize($a);
        foreach (self::normalize($b) as $key => $value) {
            $out[$key] = round($out[$key] + $value, 2);
        }

        return $out;
    }

    /**
     * @param  array<string, float>  $totals
     * @return array<string, float>
     */
    public static function average(array $totals, int $days): array
    {
        $days = max(1, $days);
        $out = [];
        foreach (self::normalize($totals) as $key => $value) {
            $out[$key] = round($value / $days, 2);
        }

        return $out;
    }

    /**
     * Lista lista para UI/API con % CDR.
     *
     * @param  array<string, float>  $values
     * @return list<array{key: string, label: string, group: string, value: float, target: float, unit: string, pct: float}>
     */
    public static function itemsForUi(array $values, ?string $groupFilter = null): array
    {
        $values = self::normalize($values);
        $items = [];

        foreach (self::catalog() as $row) {
            if ($groupFilter && $groupFilter !== 'all' && $row['group'] !== $groupFilter) {
                continue;
            }
            $value = $values[$row['key']] ?? 0.0;
            $target = $row['target'];
            $items[] = [
                'key' => $row['key'],
                'label' => $row['label'],
                'group' => $row['group'],
                'value' => $value,
                'target' => $target,
                'unit' => $row['unit'],
                'pct' => $target > 0 ? min(100, round(($value / $target) * 100)) : 0.0,
            ];
        }

        return $items;
    }
}
