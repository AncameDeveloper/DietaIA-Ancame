<div>
    <h1>Registrar comida</h1>
    <p class="muted">Describe el plato o sube una foto. La IA estimará calorías y nutrientes.</p>

    <div class="grid grid-2">
        <div class="card">
            <h2 style="margin-top:0">Por texto</h2>
            <form wire:submit="saveText">
                <label>Fecha</label>
                <input type="date" wire:model="eaten_on">
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
                <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">Analizar y guardar</button>
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
            <button class="btn btn-primary" wire:click="analyzePhoto" wire:loading.attr="disabled">Analizar foto</button>

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
