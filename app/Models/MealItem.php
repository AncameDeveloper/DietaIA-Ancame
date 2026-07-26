<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealItem extends Model
{
    protected $fillable = [
        'meal_id',
        'food_id',
        'name',
        'quantity_g',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'micros',
    ];

    protected function casts(): array
    {
        return [
            'micros' => 'array',
            'quantity_g' => 'decimal:2',
            'calories' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'carbs_g' => 'decimal:2',
            'fat_g' => 'decimal:2',
        ];
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}
