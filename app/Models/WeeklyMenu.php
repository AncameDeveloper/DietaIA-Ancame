<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeeklyMenu extends Model
{
    protected $fillable = [
        'user_id',
        'diet_plan_id',
        'week_start',
        'horizon',
        'content',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'week_start' => 'date',
            'content' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function dietPlan(): BelongsTo
    {
        return $this->belongsTo(DietPlan::class);
    }
}
