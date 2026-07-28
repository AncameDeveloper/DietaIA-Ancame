<div>
    <div class="nav" style="margin-bottom:.75rem">
        <div>
            <h1 style="margin:0">Progreso</h1>
            <p class="muted" style="margin:.25rem 0 0">Seguimiento de peso, rachas y consejos personalizados</p>
        </div>
        <button type="button" class="btn btn-primary" wire:click="saveWeight">Registrar peso de hoy</button>
    </div>

    @if ($errorMessage)
        <div class="error">{{ $errorMessage }}</div>
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

    <div class="card">
        <h2 style="margin-top:0">Registrar peso de hoy</h2>
        <div class="grid grid-3">
            <div>
                <label>Peso de hoy (kg)</label>
                <input type="number" step="0.1" wire:model="todayWeight">
            </div>
            <div>
                <label>Peso inicial (kg)</label>
                <input type="number" step="0.1" wire:model="startWeight">
            </div>
            <div>
                <label>Peso objetivo (kg)</label>
                <input type="number" step="0.1" wire:model="targetWeight">
            </div>
        </div>
        <label>Nota (opcional)</label>
        <input type="text" wire:model="note" placeholder="Ej: en ayunas">
        <button type="button" class="btn btn-primary" wire:click="saveWeight">Guardar peso</button>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Evolución del peso</h2>
        @if ($chart['points'])
            <div class="weight-chart-wrap">
                <svg viewBox="0 0 100 48" class="weight-chart" preserveAspectRatio="none" role="img" aria-label="Gráfico de peso">
                    <defs>
                        <linearGradient id="weightFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#2f6f4e" stop-opacity="0.25"/>
                            <stop offset="100%" stop-color="#2f6f4e" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <polyline fill="none" stroke="#2f6f4e" stroke-width="1.8" points="{{ $chart['points'] }}"></polyline>
                    @foreach ($chart['dots'] as $dot)
                        <circle cx="{{ $dot['x'] }}" cy="{{ $dot['y'] }}" r="1.6" fill="#2f6f4e"></circle>
                    @endforeach
                </svg>
                <div class="chart-scale muted">
                    <span>{{ number_format($chart['max'], 1) }} kg</span>
                    <span>{{ number_format($chart['min'], 1) }} kg</span>
                </div>
            </div>
            <div class="chart-labels muted">
                @foreach ($chart['labels'] as $label)
                    <span>{{ $label }}</span>
                @endforeach
            </div>
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
