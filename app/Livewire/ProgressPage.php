<?php

namespace App\Livewire;

use App\Models\DailySummary;
use App\Models\WeightLog;
use App\Services\NutritionAiService;
use App\Support\Labels;
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
     * @return array<string, mixed>
     */
    private function progressStats(): array
    {
        $user = auth()->user()->load('profile');
        $logs = WeightLog::query()
            ->where('user_id', $user->id)
            ->orderBy('logged_on')
            ->get(['logged_on', 'weight_kg']);

        $start = (float) ($user->profile?->start_weight_kg ?? $logs->first()?->weight_kg ?? $user->profile?->weight_kg ?? 0);
        $current = (float) ($user->profile?->weight_kg ?? $logs->last()?->weight_kg ?? 0);
        $target = (float) ($user->profile?->target_weight_kg ?? max(0, $start - 8));

        $weeklyRate = null;
        if ($logs->count() >= 2) {
            $first = $logs->first();
            $last = $logs->last();
            $days = max(1, $first->logged_on->diffInDays($last->logged_on));
            $weeklyRate = round((((float) $last->weight_kg - (float) $first->weight_kg) / $days) * 7, 2);
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
            'logs' => $logs->take(-14)->map(fn ($l) => [
                'date' => $l->logged_on->toDateString(),
                'weight_kg' => (float) $l->weight_kg,
            ])->values()->all(),
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
        $logs = WeightLog::query()
            ->where('user_id', auth()->id())
            ->orderBy('logged_on')
            ->get();

        $chart = $this->buildChartPoints($logs);
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
            'logs' => $logs,
            'chart' => $chart,
            'milestones' => $milestones,
            'progressPct' => round($progressPct, 1),
        ]);
    }

    /**
     * @return array{points: string, labels: array<int, string>, min: float, max: float}
     */
    private function buildChartPoints($logs): array
    {
        if ($logs->isEmpty()) {
            return ['points' => '', 'labels' => [], 'min' => 0, 'max' => 0, 'dots' => []];
        }

        $values = $logs->map(fn ($l) => (float) $l->weight_kg)->all();
        $min = min($values);
        $max = max($values);
        $span = max(1, $max - $min);
        $count = max(1, count($values) - 1);
        $width = 100;
        $height = 40;
        $pad = 4;

        $points = [];
        $dots = [];
        foreach ($values as $i => $value) {
            $x = $count === 0 ? 0 : ($i / $count) * $width;
            $y = $pad + (1 - (($value - $min) / $span)) * ($height - 2 * $pad);
            $points[] = round($x, 2).','.round($y, 2);
            $dots[] = ['x' => round($x, 2), 'y' => round($y, 2), 'value' => $value];
        }

        return [
            'points' => implode(' ', $points),
            'labels' => $logs->map(fn ($l) => $l->logged_on->format('d/m'))->all(),
            'min' => $min,
            'max' => $max,
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
