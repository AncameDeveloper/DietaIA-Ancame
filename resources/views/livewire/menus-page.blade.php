<div>
    <div class="nav">
        <div>
            <h1 style="margin:0">Menús recomendados</h1>
            <p class="muted">Genera un menú diario o semanal según tu plan.</p>
        </div>
        <div class="nav-links">
            <button class="btn" wire:click="generate('daily')" wire:loading.attr="disabled">Menú diario</button>
            <button class="btn btn-primary" wire:click="generate('weekly')" wire:loading.attr="disabled">Menú semanal</button>
        </div>
    </div>

    @if ($latest)
        <div class="card">
            <h2 style="margin-top:0">{{ $latest->horizon === 'weekly' ? 'Semanal' : 'Diario' }} · {{ $latest->created_at?->format('d/m/Y H:i') }}</h2>
            @if ($latest->notes)
                <p class="muted">{{ $latest->notes }}</p>
            @endif

            @foreach (($latest->content['days'] ?? []) as $day)
                <div style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--line)">
                    <h3 style="margin:0 0 .5rem">{{ $day['date_label'] ?? ('Día '.$day['day']) }}</h3>
                    @foreach (($day['meals'] ?? []) as $meal)
                        <div class="meal-row">
                            <div>
                                <strong>{{ $meal['title'] ?? 'Comida' }}</strong>
                                <div class="muted">{{ $meal['meal_type'] ?? '' }} · {{ $meal['description'] ?? '' }}</div>
                            </div>
                            <div style="text-align:right">
                                <strong>{{ round($meal['calories'] ?? 0) }} kcal</strong>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @else
        <div class="card">
            <p class="muted">Aún no hay menús. Genera el primero.</p>
        </div>
    @endif
</div>
