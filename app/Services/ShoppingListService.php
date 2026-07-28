<?php

namespace App\Services;

use Illuminate\Support\Str;

class ShoppingListService
{
    /**
     * Construye una lista de la compra consolidada a partir del contenido de un menú.
     *
     * @param  array<string, mixed>  $content  Estructura { days: [ { meals: [...] } ] } o lista de días.
     * @return list<array{name: string, quantity_g: float|null, unit: string, quantity_label: string, sources: list<string>}>
     */
    public function buildFromMenuContent(array $content): array
    {
        $days = $content['days'] ?? (array_is_list($content) ? $content : []);
        $bucket = [];

        foreach ($days as $day) {
            if (! is_array($day)) {
                continue;
            }
            $dayLabel = (string) ($day['date_label'] ?? ('Día '.($day['day'] ?? '')));

            foreach (($day['meals'] ?? []) as $meal) {
                if (! is_array($meal)) {
                    continue;
                }
                $mealTitle = (string) ($meal['title'] ?? 'Comida');
                $source = trim($dayLabel.' · '.$mealTitle);

                foreach ($this->extractIngredientsFromMeal($meal) as $ingredient) {
                    $key = $this->normalizeKey($ingredient['name']);
                    if ($key === '') {
                        continue;
                    }

                    if (! isset($bucket[$key])) {
                        $bucket[$key] = [
                            'name' => $this->displayName($ingredient['name']),
                            'quantity_g' => null,
                            'unit' => 'g',
                            'sources' => [],
                        ];
                    }

                    if ($ingredient['quantity_g'] !== null) {
                        $bucket[$key]['quantity_g'] = ($bucket[$key]['quantity_g'] ?? 0) + (float) $ingredient['quantity_g'];
                    }

                    if ($source !== '' && ! in_array($source, $bucket[$key]['sources'], true)) {
                        $bucket[$key]['sources'][] = $source;
                    }
                }
            }
        }

        $items = array_values(array_map(function (array $item) {
            $qty = $item['quantity_g'];
            $item['quantity_g'] = $qty !== null ? round((float) $qty, 1) : null;
            $item['quantity_label'] = $this->formatQuantityLabel($item['quantity_g'], $item['unit']);

            return $item;
        }, $bucket));

        usort($items, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

        return $items;
    }

    /**
     * @param  array<string, mixed>  $meal
     * @return list<array{name: string, quantity_g: float|null}>
     */
    private function extractIngredientsFromMeal(array $meal): array
    {
        $out = [];

        foreach (['items', 'ingredients', 'ingredient_list'] as $key) {
            if (empty($meal[$key]) || ! is_array($meal[$key])) {
                continue;
            }
            foreach ($meal[$key] as $row) {
                if (is_string($row)) {
                    $parsed = $this->parseIngredientPhrase($row);
                    if ($parsed) {
                        $out[] = $parsed;
                    }
                    continue;
                }
                if (! is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? $row['ingredient'] ?? $row['alimento'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $qty = $row['quantity_g'] ?? $row['cantidad_g'] ?? $row['grams'] ?? $row['quantity'] ?? null;
                $out[] = [
                    'name' => $name,
                    'quantity_g' => is_numeric($qty) ? (float) $qty : null,
                ];
            }
        }

        if ($out !== []) {
            return $out;
        }

        // Fallback: inferir ingredientes de description / title.
        $text = trim((string) ($meal['description'] ?? ''));
        if ($text === '') {
            $text = trim((string) ($meal['title'] ?? ''));
        }

        foreach ($this->splitIngredientPhrases($text) as $phrase) {
            $parsed = $this->parseIngredientPhrase($phrase);
            if ($parsed) {
                $out[] = $parsed;
            }
        }

        if ($out === [] && filled($meal['title'] ?? null)) {
            $out[] = [
                'name' => (string) $meal['title'],
                'quantity_g' => null,
            ];
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function splitIngredientPhrases(string $text): array
    {
        $text = Str::of($text)
            ->replace(['/', ';', '+'], ',')
            ->replace([' con ', ' y ', ' e ', ' & '], ', ')
            ->toString();

        return collect(preg_split('/[,]+/u', $text) ?: [])
            ->map(fn ($p) => trim((string) $p))
            ->filter(fn ($p) => mb_strlen($p) >= 2)
            ->reject(fn ($p) => $this->isNoisePhrase($p))
            ->values()
            ->all();
    }

    /**
     * @return array{name: string, quantity_g: float|null}|null
     */
    private function parseIngredientPhrase(string $phrase): ?array
    {
        $phrase = trim($phrase);
        if ($phrase === '' || $this->isNoisePhrase($phrase)) {
            return null;
        }

        $quantity = null;
        $name = $phrase;

        // "150 g de pollo", "200g pollo", "2 uds huevos"
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*(kg|g|gr|gramos|ml|ud|uds|unidad|unidades)?\s*(?:de\s+)?(.+)$/iu', $phrase, $m)) {
            $num = (float) str_replace(',', '.', $m[1]);
            $unit = Str::lower($m[2] ?? 'g');
            $name = trim($m[3]);

            $quantity = match ($unit) {
                'kg' => $num * 1000,
                'ud', 'uds', 'unidad', 'unidades' => $num * 100,
                'ml', 'g', 'gr', 'gramos', '' => $num,
                default => $num,
            };
        }

        $name = preg_replace('/\s+/u', ' ', $name) ?? $name;
        $name = trim($name, " \t\n\r\0\x0B-–—");
        if (mb_strlen($name) < 2) {
            return null;
        }

        return [
            'name' => $name,
            'quantity_g' => $quantity,
        ];
    }

    private function isNoisePhrase(string $phrase): bool
    {
        $p = Str::lower(trim($phrase));
        $noise = [
            'al gusto', 'opcional', 'sal', 'pimienta', 'agua', 'aceite de oliva al gusto',
            'desayuno', 'almuerzo', 'comida', 'cena', 'snack', 'merienda',
        ];

        return in_array($p, $noise, true) || mb_strlen($p) < 2;
    }

    private function normalizeKey(string $name): string
    {
        $key = Str::lower(trim($name));
        $key = Str::ascii($key);
        $key = preg_replace('/[^a-z0-9\s]/', '', $key) ?? $key;
        $key = preg_replace('/\s+/', ' ', $key) ?? $key;

        $words = collect(explode(' ', $key))
            ->filter()
            ->map(function (string $word) {
                if (Str::endsWith($word, 'es') && mb_strlen($word) > 4) {
                    return Str::substr($word, 0, -2);
                }
                if (Str::endsWith($word, 's') && mb_strlen($word) > 3) {
                    return Str::substr($word, 0, -1);
                }

                return $word;
            })
            ->implode(' ');

        return trim($words);
    }

    private function displayName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? $name);

        return Str::ucfirst($name);
    }

    private function formatQuantityLabel(?float $quantityG, string $unit): string
    {
        if ($quantityG === null || $quantityG <= 0) {
            return 'Al gusto / según receta';
        }

        if ($quantityG >= 1000) {
            return rtrim(rtrim(number_format($quantityG / 1000, 2, ',', ''), '0'), ',').' kg';
        }

        return rtrim(rtrim(number_format($quantityG, 1, ',', ''), '0'), ',').' '.$unit;
    }
}
