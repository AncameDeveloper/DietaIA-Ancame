<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Meal extends Model
{
    protected $fillable = [
        'user_id',
        'eaten_on',
        'meal_type',
        'title',
        'description',
        'photo_path',
        'source',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'fiber_g',
        'micros',
        'ai_confidence',
        'confirmed',
    ];

    protected function casts(): array
    {
        return [
            'eaten_on' => 'date',
            'micros' => 'array',
            'calories' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'carbs_g' => 'decimal:2',
            'fat_g' => 'decimal:2',
            'fiber_g' => 'decimal:2',
            'ai_confidence' => 'decimal:2',
            'confirmed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MealItem::class);
    }
}
