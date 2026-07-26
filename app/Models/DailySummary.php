<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailySummary extends Model
{
    protected $fillable = [
        'user_id',
        'summary_date',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
        'fiber_g',
        'water_glasses',
        'micros',
    ];

    protected function casts(): array
    {
        return [
            'summary_date' => 'date',
            'micros' => 'array',
            'calories' => 'decimal:2',
            'protein_g' => 'decimal:2',
            'carbs_g' => 'decimal:2',
            'fat_g' => 'decimal:2',
            'fiber_g' => 'decimal:2',
            'water_glasses' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
