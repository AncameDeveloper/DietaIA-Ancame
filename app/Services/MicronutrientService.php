<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\User;
use App\Support\Micronutrients;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class MicronutrientService
{
    /**
     * Totales / promedio de micronutrientes para un usuario.
     *
     * @return array{
     *   range: string,
     *   from: string,
     *   to: string,
     *   days_counted: int,
     *   micros: array<string, float>,
     *   items: list<array<string, mixed>>,
     *   groups: array<string, string>,
     *   info: string
     * }
     */
    public function forUser(User $user, string $range = '7days', CarbonInterface|string|null $asOf = null, string $group = 'all'): array
    {
        $range = in_array($range, ['today', '7days'], true) ? $range : '7days';
        $to = $asOf instanceof CarbonInterface
            ? $asOf->copy()->startOfDay()
            : Carbon::parse($asOf ?: now()->toDateString())->startOfDay();

        if ($range === 'today') {
            $from = $to->copy();
            [$micros, $daysCounted] = $this->sumForPeriod((int) $user->id, $from->toDateString(), $to->toDateString(), average: false);
        } else {
            $from = $to->copy()->subDays(6);
            [$micros, $daysCounted] = $this->sumForPeriod((int) $user->id, $from->toDateString(), $to->toDateString(), average: true);
        }

        return [
            'range' => $range,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days_counted' => $daysCounted,
            'micros' => $micros,
            'items' => Micronutrients::itemsForUi($micros, $group),
            'groups' => Micronutrients::groupLabels(),
            'info' => Micronutrients::INFO_MESSAGE,
        ];
    }

    /**
     * @return array{0: array<string, float>, 1: int}
     */
    private function sumForPeriod(int $userId, string $from, string $to, bool $average): array
    {
        $meals = Meal::query()
            ->where('user_id', $userId)
            ->where('confirmed', true)
            ->whereDate('eaten_on', '>=', $from)
            ->whereDate('eaten_on', '<=', $to)
            ->get(['eaten_on', 'micros']);

        $totals = Micronutrients::normalize([]);
        $days = [];

        foreach ($meals as $meal) {
            $day = $meal->eaten_on instanceof CarbonInterface
                ? $meal->eaten_on->toDateString()
                : Carbon::parse($meal->eaten_on)->toDateString();
            $days[$day] = true;
            $totals = Micronutrients::sum($totals, is_array($meal->micros) ? $meal->micros : []);
        }

        $daysCounted = count($days);

        if ($average) {
            if ($daysCounted === 0) {
                return [Micronutrients::normalize([]), 0];
            }

            return [Micronutrients::average($totals, $daysCounted), $daysCounted];
        }

        // Hoy: totales del día (sin dividir).
        return [$totals, $daysCounted > 0 ? 1 : 0];
    }
}
