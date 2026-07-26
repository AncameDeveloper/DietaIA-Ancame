<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Food extends Model
{
    protected $table = 'foods';

    protected $fillable = [
        'name',
        'category',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'fiber_g',
        'micros',
        'serving_g',
    ];

    protected function casts(): array
    {
        return [
            'micros' => 'array',
            'calories' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'carbs_g' => 'decimal:2',
            'fat_g' => 'decimal:2',
            'fiber_g' => 'decimal:2',
        ];
    }
}
