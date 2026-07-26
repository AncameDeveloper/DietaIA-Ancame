<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Profile extends Model
{
    protected $fillable = [
        'user_id',
        'age',
        'sex',
        'weight_kg',
        'start_weight_kg',
        'target_weight_kg',
        'height_cm',
        'activity_level',
        'goal',
        'calorie_target',
        'protein_target_g',
        'carbs_target_g',
        'fat_target_g',
        'restrictions',
        'allergies',
        'onboarding_completed',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'start_weight_kg' => 'decimal:2',
            'target_weight_kg' => 'decimal:2',
            'height_cm' => 'decimal:2',
            'restrictions' => 'array',
            'allergies' => 'array',
            'onboarding_completed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
