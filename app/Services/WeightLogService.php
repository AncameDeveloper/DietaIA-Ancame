<?php

namespace App\Services;

use App\Models\User;
use App\Models\WeightLog;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class WeightLogService
{
    /**
     * Registra o actualiza el peso de un día (upsert por user_id + logged_on).
     */
    public function upsert(
        User $user,
        float $weightKg,
        ?string $date = null,
        ?string $note = null,
        ?float $startWeight = null,
        ?float $targetWeight = null,
    ): WeightLog {
        $loggedOn = $this->normalizeDate($date);

        $profile = $user->profile()->firstOrCreate([]);

        if (! $profile->start_weight_kg && $startWeight === null) {
            $startWeight = $profile->weight_kg
                ? (float) $profile->weight_kg
                : $weightKg;
        }

        $log = WeightLog::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'logged_on' => $loggedOn,
            ],
            [
                'weight_kg' => $weightKg,
                'note' => $note !== null && $note !== '' ? $note : null,
            ]
        );

        $fill = [];
        if ($startWeight !== null) {
            $fill['start_weight_kg'] = $startWeight;
        } elseif (! $profile->start_weight_kg) {
            $fill['start_weight_kg'] = $weightKg;
        }
        if ($targetWeight !== null) {
            $fill['target_weight_kg'] = $targetWeight;
        }

        // El "peso actual" del perfil sigue el registro más reciente.
        $latest = WeightLog::query()
            ->where('user_id', $user->id)
            ->orderByDesc('logged_on')
            ->orderByDesc('id')
            ->first();

        if ($latest && $latest->logged_on->toDateString() === $loggedOn) {
            $fill['weight_kg'] = $weightKg;
        }

        if ($fill !== []) {
            $profile->fill($fill)->save();
        }

        return $log->fresh();
    }

    private function normalizeDate(?string $date): string
    {
        $day = $date
            ? Carbon::parse($date)->startOfDay()
            : now()->startOfDay();

        if ($day->gt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'date' => 'No se puede registrar peso en una fecha futura.',
            ]);
        }

        return $day->toDateString();
    }
}
