<div>
    <div class="nav">
        <div>
            <h1 style="margin:0">Consejos de alimentación</h1>
            <p class="muted">Recomendaciones contextuales según tu plan e historial.</p>
        </div>
        <button class="btn" wire:click="refreshTips">Actualizar</button>
    </div>

    <div class="grid grid-2">
        @forelse ($tips as $tip)
            <div class="card">
                <h2 style="margin-top:0">{{ $tip['title'] ?? 'Consejo' }}</h2>
                <p>{{ $tip['body'] ?? '' }}</p>
            </div>
        @empty
            <div class="card"><p class="muted">No hay consejos todavía.</p></div>
        @endforelse
    </div>
</div>
