<div>
    <div class="nav">
        <div>
            <h1 style="margin:0">Planes de dieta</h1>
            <p class="muted">Elige manualmente o deja que la IA sugiera.</p>
        </div>
        <button
            class="btn btn-primary"
            wire:click="suggest"
            wire:loading.attr="disabled"
            wire:target="suggest"
        >
            <span wire:loading.remove wire:target="suggest">Sugerir con IA</span>
            <span wire:loading wire:target="suggest">Pensando…</span>
        </button>
    </div>

    <x-ai-busy
        targets="suggest"
        :messages="[
            'Revisando tu perfil y objetivos…',
            'Comparando planes disponibles…',
            'Eligiendo la mejor opción…',
            'Casi listo…',
        ]"
    />

    @if ($active?->dietPlan)
        <div class="success">Plan activo: <strong>{{ $active->dietPlan->name }}</strong> ({{ $active->source }})</div>
    @endif
    @if ($suggestionReason)
        <div class="alert">{{ $suggestionReason }}</div>
    @endif

    <div class="grid grid-2" wire:loading.class="is-dimmed" wire:target="suggest">
        @foreach ($plans as $plan)
            <div class="card">
                <h2 style="margin-top:0">{{ $plan->name }}</h2>
                <p class="muted">{{ $plan->description }}</p>
                @if ($plan->macros_ratio)
                    <p class="muted">
                        Macros:
                        P {{ round(($plan->macros_ratio['protein'] ?? 0)*100) }}% ·
                        C {{ round(($plan->macros_ratio['carbs'] ?? 0)*100) }}% ·
                        G {{ round(($plan->macros_ratio['fat'] ?? 0)*100) }}%
                    </p>
                @endif
                <button class="btn" wire:click="select({{ $plan->id }})" wire:loading.attr="disabled">Seleccionar</button>
            </div>
        @endforeach
    </div>
</div>
