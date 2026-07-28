<?php

namespace App\Livewire;

use App\Models\DailySummary;
use App\Models\WeightLog;
use App\Services\NutritionAiService;
use App\Support\Labels;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Progreso — DietaIA')]
class ProgressPage extends Component
{
    public ?float $todayWeight = null;

    public ?float $targetWeight = null;

    public ?float $startWeight = null;

    public string $note = '';

    public array $aiTips = [];

    public string $aiSummary = '';

    public string $errorMessage = '';

    public function mount(): void
    {
        $profile = auth()->user()->profile;
        $this->todayWeight = $profile?->weight_kg ? (float) $profile->weight_kg : null;
        $this->targetWeight = $profile?->target_weight_kg ? (float) $profile->target_weight_kg : null;
        $this->startWeight = $profile?->start_weight_kg
            ? (float) $profile->start_weight_kg
            : ($profile?->weight_kg ? (float) $profile->weight_kg : null);

        $todayLog = WeightLog::query()
            ->where('user_id', auth()->id())
            ->whereDate('logged_on', now()->toDateString())
            ->first();

        if ($todayLog) {
            $this->todayWeight = (float) $todayLog->weight_kg;
        }
    }

    public function saveWeight(): void
    {
        $this->errorMessage = '';

        $data = $this->validate([
            'todayWeight' => ['required', 'numeric', 'min:30', 'max:300'],
            'targetWeight' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'startWeight' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $user = auth()->user();
        $profile = $user->profile()->firstOrCreate([]);

        if (! $profile->start_weight_kg && empty($data['startWeight'])) {
            $data['startWeight'] = $profile->weight_kg ? (float) $profile->weight_kg : $data['todayWeight'];
        }

        WeightLog::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'logged_on' => now()->toDateString(),
            ],
            [
                'weight_kg' => $data['todayWeight'],
                'note' => $data['note'] ?: null,
            ]
        );

        $profile->fill([
            'weight_kg' => $data['todayWeight'],
            'start_weight_kg' => $data['startWeight'] ?? $profile->start_weight_kg ?? $data['todayWeight'],
            'target_weight_kg' => $data['targetWeight'] ?? $profile->target_weight_kg,
        ])->save();

        $this->startWeight = (float) $profile->start_weight_kg;
        $this->targetWeight = $profile->target_weight_kg ? (float) $profile->target_weight_kg : null;
        $this->aiTips = [];
        $this->aiSummary = '';

        session()->flash('status', 'Peso de hoy registrado.');
    }

    public function refreshAiTips(NutritionAiService $ai): void
    {
        $this->errorMessage = '';
        $stats = $this->progressStats();

        try {
            $result = $ai->progressTips(auth()->user(), $stats);
            $this->aiTips = $result['tips'] ?? [];
            $this->aiSummary = $result['summary'] ?? '';
        } catch (\Throwable $e) {
            $this->errorMessage = 'No se pudieron generar consejos ahora.';
        }
    }

    /**
     * Historial de pesos listo para gráfica/API: [{date, weight}, ...]
     *
     * @return list<array{date: string, weight: float}>
     */
    public static function weightHistoryForUser(int $userId, int $days = 90): array
    {
        $user = \App\Models\User::query()->with('profile')->find($userId);
        if (! $user) {
            return [];
        }

        $from = now()->subDays(max(7, $days))->startOfDay();

        $logs = WeightLog::query()
            ->where('user_id', $userId)
            ->whereDate('logged_on', '>=', $from->toDateString())
            ->orderBy('logged_on', 'asc')
            ->orderBy('id', 'asc')
            ->get(['logged_on', 'weight_kg']);

        // Un punto por día (último registro del día).
        $byDate = [];
        foreach ($logs as $log) {
            $date = $log->logged_on instanceof Carbon
                ? $log->logged_on->toDateString()
                : Carbon::parse($log->logged_on)->toDateString();
            $byDate[$date] = round((float) $log->weight_kg, 2);
        }

        $series = [];
        foreach ($byDate as $date => $weight) {
            $series[] = ['date' => $date, 'weight' => $weight];
        }

        $startWeight = $user->profile?->start_weight_kg
            ? (float) $user->profile->start_weight_kg
            : null;

        // Si solo hay 0–1 puntos, incluir peso inicial del perfil para trazar tendencia.
        if ($startWeight && count($series) <= 1) {
            $firstDate = $series[0]['date'] ?? now()->toDateString();
            $startDate = Carbon::parse($firstDate)->subDays(count($series) === 0 ? 0 : 7)->toDateString();

            // No duplicar si ya existe exactamente el mismo día.
            $hasStartDate = collect($series)->contains(fn ($p) => $p['date'] === $startDate);
            if (! $hasStartDate) {
                array_unshift($series, [
                    'date' => $startDate,
                    'weight' => round($startWeight, 2),
                ]);
            } elseif (count($series) === 1 && abs($series[0]['weight'] - $startWeight) > 0.05) {
                // Mismo día raro: empujar el inicial un día antes.
                array_unshift($series, [
                    'date' => Carbon::parse($startDate)->subDay()->toDateString(),
                    'weight' => round($startWeight, 2),
                ]);
            }
        }

        // Si tras eso sigue habiendo un solo punto, duplicar en X+1 día para línea visible.
        if (count($series) === 1) {
            $series[] = [
                'date' => Carbon::parse($series[0]['date'])->addDay()->toDateString(),
                'weight' => $series[0]['weight'],
            ];
        }

        usort($series, fn ($a, $b) => strcmp($a['date'], $b['date']));

        return array_values($series);
    }

    /**
     * @return array<string, mixed>
     */
    private function progressStats(): array
    {
        $user = auth()->user()->load('profile');
        $series = self::weightHistoryForUser((int) $user->id, 90);

        $start = (float) ($user->profile?->start_weight_kg ?? ($series[0]['weight'] ?? $user->profile?->weight_kg ?? 0));
        $current = (float) ($user->profile?->weight_kg ?? (end($series)['weight'] ?? 0));
        $target = (float) ($user->profile?->target_weight_kg ?? max(0, $start - 8));

        $weeklyRate = null;
        if (count($series) >= 2) {
            $first = $series[0];
            $last = $series[count($series) - 1];
            $days = max(1, Carbon::parse($first['date'])->diffInDays(Carbon::parse($last['date'])));
            $weeklyRate = round((($last['weight'] - $first['weight']) / $days) * 7, 2);
        }

        return [
            'goal' => $user->profile?->goal ?? 'lose_weight',
            'goal_label' => Labels::goal($user->profile?->goal ?? 'lose_weight'),
            'start_weight_kg' => $start,
            'current_weight_kg' => $current,
            'target_weight_kg' => $target,
            'delta_kg' => round($current - $start, 2),
            'weekly_rate_kg' => $weeklyRate,
            'streak_days' => $this->calorieStreak($user->id),
            'logs' => collect($series)->take(-14)->values()->map(fn ($p) => [
                'date' => $p['date'],
                'weight_kg' => $p['weight'],
            ])->all(),
        ];
    }

    private function calorieStreak(int $userId): int
    {
        $user = auth()->user()->load('profile');
        $target = (float) ($user->profile?->calorie_target ?? 0);
        if ($target <= 0) {
            return 0;
        }

        $streak = 0;
        for ($i = 0; $i < 30; $i++) {
            $day = now()->subDays($i)->toDateString();
            $summary = DailySummary::query()
                ->where('user_id', $userId)
                ->whereDate('summary_date', $day)
                ->first();

            if (! $summary) {
                break;
            }

            $calories = (float) $summary->calories;
            $within = $calories > 0 && $calories <= ($target * 1.05);
            if (! $within) {
                break;
            }
            $streak++;
        }

        return $streak;
    }

    public function render()
    {
        $stats = $this->progressStats();
        $series = self::weightHistoryForUser((int) auth()->id(), 90);
        $chart = $this->buildChartPayload($series);
        $milestones = $this->buildMilestones($stats);

        $progressPct = 0;
        $start = $stats['start_weight_kg'];
        $current = $stats['current_weight_kg'];
        $target = $stats['target_weight_kg'];
        if ($start > $target && ($start - $target) > 0) {
            $progressPct = min(100, max(0, (($start - $current) / ($start - $target)) * 100));
        } elseif ($target > $start && ($target - $start) > 0) {
            $progressPct = min(100, max(0, (($current - $start) / ($target - $start)) * 100));
        }

        return view('livewire.progress-page', [
            'stats' => $stats,
            'chart' => $chart,
            'milestones' => $milestones,
            'progressPct' => round($progressPct, 1),
        ]);
    }

    /**
     * @param  list<array{date: string, weight: float}>  $series
     * @return array<string, mixed>
     */
    private function buildChartPayload(array $series): array
    {
        if ($series === []) {
            return [
                'series' => [],
                'labels' => [],
                'weights' => [],
                'min' => 0,
                'max' => 0,
                'points' => '',
                'dots' => [],
            ];
        }

        $weights = array_column($series, 'weight');
        $rawMin = min($weights);
        $rawMax = max($weights);
        // Margen dinámico para que la línea no se colapse cuando min ≈ max.
        $min = $rawMin - 2;
        $max = $rawMax + 2;
        if ($max <= $min) {
            $max = $min + 4;
        }

        $labels = array_map(
            fn ($p) => Carbon::parse($p['date'])->format('d/m'),
            $series
        );

        $count = max(1, count($series) - 1);
        $width = 100;
        $height = 40;
        $pad = 4;
        $span = max(0.1, $max - $min);

        $points = [];
        $dots = [];
        foreach ($series as $i => $point) {
            $x = ($i / $count) * $width;
            $y = $pad + (1 - (($point['weight'] - $min) / $span)) * ($height - 2 * $pad);
            $points[] = round($x, 2).','.round($y, 2);
            $dots[] = [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'value' => $point['weight'],
                'date' => $point['date'],
            ];
        }

        return [
            'series' => $series,
            'labels' => $labels,
            'weights' => $weights,
            'min' => round($min, 1),
            'max' => round($max, 1),
            'points' => implode(' ', $points),
            'dots' => $dots,
        ];
    }

    /**
     * @param  array<string, mixed>  $stats
     * @return array<int, array{title: string, body: string, done: bool}>
     */
    private function buildMilestones(array $stats): array
    {
        $delta = abs((float) $stats['delta_kg']);
        $streak = (int) $stats['streak_days'];

        return [
            [
                'title' => 'Primera báscula',
                'body' => 'Has empezado a registrar tu peso.',
                'done' => ! empty($stats['logs']),
            ],
            [
                'title' => '1 kg de cambio',
                'body' => 'Alcanzar al menos 1 kg hacia tu objetivo.',
                'done' => $delta >= 1,
            ],
            [
                'title' => 'Racha de 3 días',
                'body' => 'Cumplir calorías objetivo 3 días seguidos.',
                'done' => $streak >= 3,
            ],
            [
                'title' => 'Racha de 7 días',
                'body' => 'Una semana completa de constancia calórica.',
                'done' => $streak >= 7,
            ],
        ];
    }
}
