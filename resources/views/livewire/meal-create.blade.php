<div>
    <x-ai-busy
        targets="saveText,analyzePhoto"
        :messages="[
            'Analizando la comida…',
            'Estimando calorías y nutrientes…',
            'Afinando la estimación…',
            'Casi listo…',
        ]"
    />

    <h1>Registrar comida</h1>
    <p class="muted">Describe el plato o sube una foto. La IA estimará calorías y nutrientes.</p>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0">Por texto</h2>
            <form wire:submit="saveText">
                <label>Fecha</label>
                <div class="date-control" title="Fecha" style="margin-bottom:.75rem">
                    <span class="date-text">{{ \App\Support\Labels::date($eaten_on) }}</span>
                    <button
                        type="button"
                        class="date-picker-trigger"
                        title="Abrir calendario"
                        aria-label="Abrir calendario"
                        onclick="(function(btn){var i=btn.parentElement.querySelector('input[type=date]');if(i.showPicker){i.showPicker()}else{i.focus();i.click()}})(this)"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </button>
                    <input type="date" class="date-input-sr" wire:model.live="eaten_on" lang="es-ES" tabindex="-1">
                </div>
                <label>Tipo</label>
                <select wire:model="meal_type">
                    <option value="breakfast">Desayuno</option>
                    <option value="lunch">Comida</option>
                    <option value="dinner">Cena</option>
                    <option value="snack">Snack</option>
                </select>
                <label>Descripción</label>
                <textarea rows="4" wire:model="description" placeholder="Ej: pechuga de pollo a la plancha con arroz y ensalada"></textarea>
                @error('description') <div class="error">{{ $message }}</div> @enderror
                <button class="btn btn-primary" type="submit" wire:loading.attr="disabled" wire:target="saveText">
                    <span wire:loading.remove wire:target="saveText">Analizar y guardar</span>
                    <span wire:loading wire:target="saveText">Analizando…</span>
                </button>
            </form>
        </div>

        <div class="card">
            <h2 style="margin-top:0">Por foto</h2>
            <label>Foto</label>
            <input type="file" accept="image/*" wire:model="photo">
            @error('photo') <div class="error">{{ $message }}</div> @enderror
            <div wire:loading wire:target="photo" class="muted">Subiendo imagen…</div>
            <label>Pista (opcional)</label>
            <input type="text" wire:model="hint" placeholder="Incluye salsa, pan, etc.">
            <button class="btn btn-primary" wire:click="analyzePhoto" wire:loading.attr="disabled" wire:target="analyzePhoto">
                <span wire:loading.remove wire:target="analyzePhoto">Analizar foto</span>
                <span wire:loading wire:target="analyzePhoto">Analizando…</span>
            </button>

            @if ($preview)
                <div class="alert" style="margin-top:1rem">
                    Revisa la estimación antes de confirmar.
                </div>
                <label>Título</label>
                <input type="text" wire:model="preview.title">
                <div class="grid grid-2">
                    <div>
                        <label>kcal</label>
                        <input type="number" step="1" wire:model="preview.calories">
                    </div>
                    <div>
                        <label>Proteína (g)</label>
                        <input type="number" step="0.1" wire:model="preview.protein_g">
                    </div>
                    <div>
                        <label>Carbos (g)</label>
                        <input type="number" step="0.1" wire:model="preview.carbs_g">
                    </div>
                    <div>
                        <label>Grasas (g)</label>
                        <input type="number" step="0.1" wire:model="preview.fat_g">
                    </div>
                </div>
                <button class="btn btn-primary" wire:click="confirmPhoto">Confirmar comida</button>
            @endif
        </div>
    </div>
</div>
