<div>
    <h1>Tu perfil</h1>
    <p class="muted">Usamos estos datos para calcular calorías objetivo (Mifflin-St Jeor).</p>
    <div class="card">
        <form wire:submit="save">
            <div class="grid grid-2">
                <div>
                    <label>Nombre</label>
                    <input type="text" wire:model="name">
                    @error('name') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>Edad</label>
                    <input type="number" wire:model="age">
                    @error('age') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label>Sexo</label>
                    <select wire:model="sex">
                        <option value="female">Mujer</option>
                        <option value="male">Hombre</option>
                        <option value="other">Otro</option>
                    </select>
                </div>
                <div>
                    <label>Objetivo</label>
                    <select wire:model="goal">
                        <option value="lose_weight">Perder peso</option>
                        <option value="maintain">Mantener</option>
                        <option value="gain_muscle">Ganar músculo</option>
                    </select>
                </div>
                <div>
                    <label>Peso actual (kg)</label>
                    <input type="number" step="0.1" wire:model="weight_kg">
                </div>
                <div>
                    <label>Peso inicial (kg)</label>
                    <input type="number" step="0.1" wire:model="start_weight_kg">
                </div>
                <div>
                    <label>Peso objetivo (kg)</label>
                    <input type="number" step="0.1" wire:model="target_weight_kg">
                </div>
                <div>
                    <label>Altura (cm)</label>
                    <input type="number" step="0.1" wire:model="height_cm">
                </div>
                <div>
                    <label>Actividad</label>
                    <select wire:model="activity_level">
                        <option value="sedentary">Sedentario</option>
                        <option value="light">Ligera</option>
                        <option value="moderate">Moderada</option>
                        <option value="active">Activa</option>
                        <option value="very_active">Muy activa</option>
                    </select>
                </div>
            </div>
            <label>Alergias (separadas por coma)</label>
            <input type="text" wire:model="allergies_text" placeholder="gluten, lactosa...">
            <label>Restricciones (separadas por coma)</label>
            <input type="text" wire:model="restrictions_text" placeholder="vegano, sin cerdo...">
            <button class="btn btn-primary" type="submit">Guardar y calcular objetivos</button>
        </form>
    </div>
</div>
