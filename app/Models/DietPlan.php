<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DietPlan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'macros_ratio',
        'rules',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'macros_ratio' => 'array',
            'rules' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(UserDietAssignment::class);
    }
}
