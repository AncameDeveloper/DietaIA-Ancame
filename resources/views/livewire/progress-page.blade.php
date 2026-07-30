<div>
    <div class="nav" style="margin-bottom:.75rem">
        <div>
            <h1 style="margin:0">Progreso</h1>
            <p class="muted" style="margin:.25rem 0 0">Seguimiento de peso, rachas y consejos personalizados</p>
        </div>
        <a href="#weight-form" class="btn btn-primary">Registrar peso</a>
    </div>

    @if ($errorMessage)
        <div class="error">{{ $errorMessage }}</div>
    @endif
    @if ($errors->any())
        <div class="error">
            <ul style="margin:0;padding-left:1.1rem">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-3">
        <div class="card weight-card">
            <div class="muted">Peso inicial</div>
            <div class="stat-value">{{ number_format($stats['start_weight_kg'], 1) }} <span class="unit">kg</span></div>
        </div>
        <div class="card weight-card weight-card-current">
            <div class="muted">Peso actual</div>
            <div class="stat-value">{{ number_format($stats['current_weight_kg'], 1) }} <span class="unit">kg</span></div>
            <div class="muted">{{ $stats['delta_kg'] >= 0 ? '+' : '' }}{{ $stats['delta_kg'] }} kg desde el inicio</div>
        </div>
        <div class="card weight-card">
            <div class="muted">Peso objetivo</div>
            <div class="stat-value">{{ number_format($stats['target_weight_kg'], 1) }} <span class="unit">kg</span></div>
        </div>
    </div>

    <div class="card">
        <div class="nav" style="margin:0 0 .5rem">
            <strong>Avance hacia el objetivo</strong>
            <span class="muted">{{ $progressPct }}%</span>
        </div>
        <div class="progress progress-lg"><span style="width: {{ $progressPct }}%"></span></div>
        <p class="muted" style="margin:.6rem 0 0">Objetivo: {{ $stats['goal_label'] }}
            @if ($stats['weekly_rate_kg'] !== null)
                · ritmo ~ {{ $stats['weekly_rate_kg'] }} kg/semana
            @endif
        </p>
    </div>

    <div class="card" id="weight-form">
        <h2 style="margin-top:0">Registrar peso</h2>
        <div class="grid grid-3">
            <div>
                <label for="loggedOn">Fecha del registro</label>
                <input
                    id="loggedOn"
                    type="date"
                    max="{{ now()->toDateString() }}"
                    wire:model.live="loggedOn"
                >
                @error('loggedOn') <div class="error" style="margin-top:-.5rem;margin-bottom:.75rem">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="todayWeight">Peso (kg)</label>
                <input
                    id="todayWeight"
                    type="number"
                    step="0.1"
                    min="30"
                    max="300"
                    inputmode="decimal"
                    wire:model.live="todayWeight"
                    placeholder="Ej: 72.5"
                >
                @error('todayWeight') <div class="error" style="margin-top:-.5rem;margin-bottom:.75rem">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="startWeight">Peso inicial (kg)</label>
                <input id="startWeight" type="number" step="0.1" min="30" max="300" wire:model.live="startWeight">
                @error('startWeight') <div class="error" style="margin-top:-.5rem;margin-bottom:.75rem">{{ $message }}</div> @enderror
            </div>
        </div>
        <div class="grid grid-2">
            <div>
                <label for="targetWeight">Peso objetivo (kg)</label>
                <input id="targetWeight" type="number" step="0.1" min="30" max="300" wire:model.live="targetWeight">
                @error('targetWeight') <div class="error" style="margin-top:-.5rem;margin-bottom:.75rem">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="weightNote">Nota (opcional)</label>
                <input id="weightNote" type="text" wire:model.live="note" placeholder="Ej: en ayunas">
            </div>
        </div>
        <button
            type="button"
            class="btn btn-primary"
            wire:click="saveWeight"
            wire:loading.attr="disabled"
            wire:target="saveWeight"
        >
            <span wire:loading.remove wire:target="saveWeight">Guardar peso</span>
            <span wire:loading wire:target="saveWeight">Guardando…</span>
        </button>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Evolución del peso</h2>
        @if (!empty($chart['series']))
            <div
                class="weight-chart-wrap"
                wire:key="weight-chart-{{ md5(json_encode($chart['series']).$chart['min'].$chart['max']) }}"
            >
                <div class="weight-chart-canvas-wrap">
                    <canvas
                        class="js-weight-evolution-chart"
                        data-labels='@json($chart['labels'])'
                        data-weights='@json($chart['weights'])'
                        data-min="{{ $chart['min'] }}"
                        data-max="{{ $chart['max'] }}"
                        aria-label="Gráfico de evolución del peso"
                    ></canvas>
                </div>
                <div class="chart-scale muted">
                    <span>{{ number_format($chart['max'], 1) }} kg</span>
                    <span>{{ number_format($chart['min'], 1) }} kg</span>
                </div>
            </div>
            <p class="muted" style="margin:.5rem 0 0;font-size:.82rem">
                {{ count($chart['series']) }} puntos · del {{ \App\Support\Labels::date($chart['series'][0]['date'] ?? null) }}
                al {{ \App\Support\Labels::date($chart['series'][count($chart['series']) - 1]['date'] ?? null) }}
            </p>
        @else
            <p class="muted">Todavía no hay registros de peso. Añade el de hoy para empezar el gráfico.</p>
        @endif
    </div>

    <div class="card">
        <div class="nav" style="margin:0 0 .75rem">
            <div>
                <h2 style="margin:0">Consejos de IA personalizados</h2>
                <p class="muted" style="margin:.25rem 0 0">Según tu ritmo, objetivo y racha actual</p>
            </div>
            <button type="button" class="btn" wire:click="refreshAiTips" wire:loading.attr="disabled" wire:target="refreshAiTips">
                <span wire:loading.remove wire:target="refreshAiTips">Actualizar consejos</span>
                <span wire:loading wire:target="refreshAiTips">Generando…</span>
            </button>
        </div>
        <x-ai-busy
            targets="refreshAiTips"
            :messages="[
                'Analizando tu progreso…',
                'Generando consejos personalizados…',
                'Afinando recomendaciones…',
                'Casi listo…',
            ]"
        />
        @if ($aiSummary)
            <div class="alert">{{ $aiSummary }}</div>
        @endif
        <div class="grid grid-2">
            @forelse ($aiTips as $tip)
                <div class="tip-card tip-{{ $tip['tone'] ?? 'practical' }}">
                    <strong>{{ $tip['title'] ?? 'Consejo' }}</strong>
                    <p>{{ $tip['body'] ?? '' }}</p>
                </div>
            @empty
                <p class="muted">Pulsa “Actualizar consejos” para obtener recomendaciones con Gemini.</p>
            @endforelse
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Hitos y hábitos</h2>
        <div class="streak-banner">
            <div>
                <div class="muted">Racha actual</div>
                <div class="stat-value" style="font-size:1.8rem">{{ $stats['streak_days'] }} días</div>
            </div>
            <p class="muted" style="margin:0">Días seguidos cumpliendo el objetivo calórico</p>
        </div>
        <div class="grid grid-2" style="margin-top:1rem">
            @foreach ($milestones as $milestone)
                <div class="milestone {{ $milestone['done'] ? 'is-done' : '' }}">
                    <div class="milestone-check">{{ $milestone['done'] ? '✓' : '○' }}</div>
                    <div>
                        <strong>{{ $milestone['title'] }}</strong>
                        <div class="muted">{{ $milestone['body'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

@assets
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endassets

@script
<script>
    const paintWeightEvolutionChart = () => {
        if (typeof Chart === 'undefined') {
            return;
        }

        document.querySelectorAll('canvas.js-weight-evolution-chart').forEach((canvas) => {
            if (canvas._weightChart) {
                canvas._weightChart.destroy();
                canvas._weightChart = null;
            }

            let labels = [];
            let weights = [];
            try {
                labels = JSON.parse(canvas.dataset.labels || '[]');
                weights = JSON.parse(canvas.dataset.weights || '[]').map(Number);
            } catch (e) {
                return;
            }

            if (!weights.length) {
                return;
            }

            const min = Number(canvas.dataset.min);
            const max = Number(canvas.dataset.max);

            canvas._weightChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Peso (kg)',
                        data: weights,
                        borderColor: '#2f6f4e',
                        backgroundColor: 'rgba(47, 111, 78, 0.18)',
                        borderWidth: 2.5,
                        pointRadius: weights.length > 20 ? 2 : 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#2f6f4e',
                        tension: 0.25,
                        fill: true,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => `${Number(ctx.parsed.y).toFixed(1)} kg`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: {
                                maxRotation: 0,
                                autoSkip: true,
                                maxTicksLimit: 8,
                                color: '#5c6f66',
                            },
                        },
                        y: {
                            min: Number.isFinite(min) ? min : undefined,
                            max: Number.isFinite(max) ? max : undefined,
                            grid: { color: 'rgba(215, 224, 217, 0.9)' },
                            ticks: {
                                color: '#5c6f66',
                                callback: (value) => `${Number(value).toFixed(1)}`,
                            },
                        },
                    },
                },
            });
        });
    };

    const schedulePaint = () => queueMicrotask(paintWeightEvolutionChart);

    schedulePaint();
    Livewire.hook('morph.updated', () => schedulePaint());
    document.addEventListener('livewire:navigated', schedulePaint);
    Livewire.on('weight-saved', () => schedulePaint());
</script>
@endscript
