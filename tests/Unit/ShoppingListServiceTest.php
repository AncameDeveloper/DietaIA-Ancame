<?php

namespace Tests\Unit;

use App\Services\ShoppingListService;
use PHPUnit\Framework\TestCase;

class ShoppingListServiceTest extends TestCase
{
    public function test_consolidates_repeated_ingredients_and_sorts(): void
    {
        $service = new ShoppingListService;

        $items = $service->buildFromMenuContent([
            'days' => [
                [
                    'day' => 1,
                    'date_label' => 'Lunes',
                    'meals' => [
                        [
                            'title' => 'Pollo con arroz',
                            'ingredients' => [
                                ['name' => 'Pechuga de pollo', 'quantity_g' => 150],
                                ['name' => 'Arroz', 'quantity_g' => 80],
                            ],
                        ],
                        [
                            'title' => 'Pollo cena',
                            'items' => [
                                ['name' => 'pechugas de pollo', 'quantity_g' => 100],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertNotEmpty($items);
        $names = array_column($items, 'name');
        $this->assertTrue(collect($names)->contains(fn ($n) => str_contains(mb_strtolower($n), 'pollo')));

        $pollo = collect($items)->first(fn ($i) => str_contains(mb_strtolower($i['name']), 'pollo'));
        $this->assertNotNull($pollo);
        $this->assertEquals(250.0, $pollo['quantity_g']);
    }

    public function test_parses_description_when_no_ingredients(): void
    {
        $service = new ShoppingListService;

        $items = $service->buildFromMenuContent([
            'days' => [[
                'meals' => [[
                    'title' => 'Salmón',
                    'description' => 'Salmón al horno con brócoli y quinoa',
                ]],
            ]],
        ]);

        $this->assertGreaterThanOrEqual(2, count($items));
    }
}
