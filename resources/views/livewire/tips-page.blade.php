<div>
    <div class="nav">
        <div>
            <h1 style="margin:0">Consejos de alimentación</h1>
            <p class="muted">Recomendaciones contextuales según tu plan e historial.</p>
        </div>
        <button
            class="btn btn-primary"
            wire:click="refreshTips"
            wire:loading.attr="disabled"
            wire:target="refreshTips"
        >
            <span wire:loading.remove wire:target="refreshTips">
                {{ empty($tips) ? 'Generar consejos' : 'Actualizar con IA' }}
            </span>
            <span wire:loading wire:target="refreshTips">Generando…</span>
        </button>
    </div>

    <x-ai-busy
        targets="refreshTips"
        :messages="[
            'Analizando tu plan e historial…',
            'Generando consejos personalizados…',
            'Afinando recomendaciones…',
            'Casi listo…',
        ]"
    />

    @if ($fromCache && ! empty($tips))
        <p class="muted" style="margin-top:0">Mostrando consejos guardados. Pulsa actualizar para regenerarlos.</p>
    @endif

    <div class="grid grid-2" wire:loading.class="is-dimmed" wire:target="refreshTips">
        @forelse ($tips as $tip)
            <div class="card">
                <h2 style="margin-top:0">{{ $tip['title'] ?? 'Consejo' }}</h2>
                <p>{{ $tip['body'] ?? '' }}</p>
            </div>
        @empty
            <div class="card">
                <p class="muted" style="margin:0">
                    Todavía no hay consejos. Pulsa <strong>Generar consejos</strong> para obtener recomendaciones con IA.
                </p>
            </div>
        @endforelse
    </div>
</div>
