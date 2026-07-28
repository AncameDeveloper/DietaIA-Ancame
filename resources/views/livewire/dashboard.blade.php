<div>
    <div class="nav" style="margin-bottom:.5rem">
        <div>
            <h1 style="margin:0">Hoy</h1>
            <p class="muted" style="margin:.2rem 0 0">
                {{ $user->activeDietAssignment?->dietPlan?->name ?? 'Sin plan seleccionado' }}
                · objetivo {{ $targets['calories'] }} kcal
            </p>
        </div>
        <div class="date-control" title="Fecha">
            <span class="date-text">{{ \App\Support\Labels::date($date) }}</span>
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
            <input type="date" class="date-input-sr" wire:model.live="date" lang="es-ES" tabindex="-1">
        </div>
    </div>

    <div class="grid grid-4">
        @php
            $calorieCurrent = (float) $summary->calories;
            $calorieTarget = max(0, (float) ($targets['calories'] ?? 0));
            $caloriesRemaining = max(0, $calorieTarget - $calorieCurrent);
            $caloriePct = $calorieTarget > 0 ? min(100, round(($calorieCurrent / $calorieTarget) * 100)) : 0;
        @endphp
        <div class="card calorie-card">
            <div class="muted">Calorías</div>
            <div class="stat-value remaining-value">{{ round($caloriesRemaining) }}</div>
            <div class="remaining-label">Calorías restantes</div>
            <div class="muted">{{ round($calorieCurrent) }} / {{ round($calorieTarget) }} kcal</div>
            <div class="progress"><span style="width: {{ $caloriePct }}%"></span></div>
        </div>
        @foreach ([
            ['label' => 'Proteína', 'key' => 'protein_g', 'unit' => 'g'],
            ['label' => 'Carbos', 'key' => 'carbs_g', 'unit' => 'g'],
            ['label' => 'Grasas', 'key' => 'fat_g', 'unit' => 'g'],
        ] as $metric)
            @php
                $current = (float) $summary->{$metric['key']};
                $target = max(1, $targets[$metric['key']]);
                $pct = min(100, round(($current / $target) * 100));
            @endphp
            <div class="card">
                <div class="muted">{{ $metric['label'] }}</div>
                <div class="stat-value">{{ round($current) }}</div>
                <div class="muted">de {{ round($target) }} {{ $metric['unit'] }}</div>
                <div class="progress"><span style="width: {{ $pct }}%"></span></div>
            </div>
        @endforeach
    </div>

    <div class="card water-card">
        <div class="water-row">
            <div>
                <div class="muted">Hidratación</div>
                <div class="water-count">💧 {{ $waterGlasses }}/8 vasos</div>
                <div class="progress" style="max-width:220px;margin-top:.45rem">
                    <span style="width: {{ min(100, round(($waterGlasses / 8) * 100)) }}%"></span>
                </div>
            </div>
            <div class="water-actions">
                <button type="button" class="btn water-btn" wire:click="removeWaterGlass" @disabled($waterGlasses <= 0)>−</button>
                <button type="button" class="btn btn-primary water-btn" wire:click="addWaterGlass">+</button>
            </div>
        </div>
    </div>

    <div class="card">
        <h2 style="margin-top:0">Micronutrientes</h2>
        <div class="grid grid-4">
            @php
                $microItems = $summary->micros ?? [];
                if (empty($microItems)) {
                    $microItems = [
                        'vitamin_c_mg' => 0,
                        'calcium_mg' => 0,
                        'iron_mg' => 0,
                        'magnesium_mg' => 0,
                    ];
                }
            @endphp
            @foreach ($microItems as $key => $value)
                @php
                    $targetMicro = \App\Support\Labels::nutrientDailyTarget($key);
                    $pctMicro = $targetMicro ? min(100, round(((float) $value / $targetMicro) * 100)) : null;
                @endphp
                <div class="micro-card">
                    <div class="muted">{{ \App\Support\Labels::nutrient($key) }}</div>
                    <strong>{{ round($value, 1) }} <span class="unit">{{ \App\Support\Labels::nutrientUnit($key) }}</span></strong>
                    @if ($targetMicro)
                        <div class="muted" style="font-size:.78rem;margin-top:.2rem">de {{ $targetMicro }} {{ \App\Support\Labels::nutrientUnit($key) }}</div>
                        <div class="progress"><span style="width: {{ $pctMicro }}%"></span></div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <div class="card">
        <div class="nav" style="margin:0 0 .75rem">
            <h2 style="margin:0">Comidas del día</h2>
        </div>

        @php
            $blocks = [
                'breakfast' => 'Desayuno',
                'lunch' => 'Almuerzo',
                'dinner' => 'Cena',
                'snack' => 'Snacks',
            ];
        @endphp

        @foreach ($blocks as $type => $label)
            <div class="meal-block">
                <div class="meal-block-title">
                    <span class="meal-icon">{{ \App\Support\Labels::mealBlockIcon($type) }}</span>
                    {{ $label }}
                </div>
                @php $blockMeals = $mealsByType[$type] ?? collect(); @endphp
                @forelse ($blockMeals as $meal)
                    <div class="meal-row">
                        <div>
                            <strong>{{ $meal->title }}</strong>
                            <div class="badge-row">
                                <span class="badge badge-ai">{{ \App\Support\Labels::mealSource($meal->source) }}</span>
                                @if(!$meal->confirmed)
                                    <span class="badge badge-warn">Pendiente</span>
                                @endif
                            </div>
                        </div>
                        <div class="meal-row-actions">
                            <div style="text-align:right">
                                <strong>{{ round($meal->calories) }} kcal</strong>
                                <div class="muted">P {{ round($meal->protein_g) }} · C {{ round($meal->carbs_g) }} · G {{ round($meal->fat_g) }}</div>
                            </div>
                            <button
                                type="button"
                                class="btn-icon-danger"
                                wire:click="deleteMeal({{ $meal->id }})"
                                wire:confirm="¿Eliminar esta comida? Esta acción no se puede deshacer."
                                title="Eliminar comida"
                                aria-label="Eliminar comida"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <polyline points="3 6 5 6 21 6"></polyline>
                                    <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                    <path d="M10 11v6"></path>
                                    <path d="M14 11v6"></path>
                                    <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <p class="muted meal-empty">Sin registros en {{ strtolower($label) }}.</p>
                @endforelse
            </div>
        @endforeach
    </div>

    <a
        href="{{ route('dashboard', ['assistant' => 1]) }}"
        class="fab-ai fab-ai-label"
        wire:click.prevent="openQuickAssistant"
        title="Asistente IA"
        aria-label="Asistente IA"
    >
        <span class="fab-spark">✨</span>
        <span class="fab-text">Asistente IA</span>
    </a>

    @if ($showQuickAssistant)
        <x-ai-busy
            targets="analyzeQuickEntry,requestSuggestions,confirmQuickEntry,acceptSuggestion"
            :messages="[
                'Analizando tus datos…',
                'Consultando al asistente nutricional…',
                'Preparando el resultado…',
                'Casi listo…',
            ]"
        />
        <div class="modal-backdrop" wire:click="closeQuickAssistant"></div>
        <div class="modal-dialog modal-dialog-lg" role="dialog" aria-modal="true" aria-labelledby="quick-ai-title">
            <div class="nav" style="margin:0 0 1rem">
                <div>
                    <h2 id="quick-ai-title" style="margin:0">Asistente nutricional</h2>
                    <p class="muted" style="margin:.25rem 0 0">Registrar comidas o pedir recomendaciones inteligentes</p>
                </div>
                <button type="button" class="btn btn-ghost" wire:click="closeQuickAssistant">Cerrar</button>
            </div>

            <div class="assistant-tabs">
                <button
                    type="button"
                    class="assistant-tab {{ $assistantMode === 'register' ? 'is-active' : '' }}"
                    wire:click="setAssistantMode('register')"
                >Registrar comida</button>
                <button
                    type="button"
                    class="assistant-tab {{ $assistantMode === 'suggest' ? 'is-active' : '' }}"
                    wire:click="setAssistantMode('suggest')"
                >Sugerir plan</button>
            </div>

            @if ($quickError)
                <div class="error">{{ $quickError }}</div>
            @endif
            @if ($quickStatus)
                <div class="success">{{ $quickStatus }}</div>
            @endif

            @if ($assistantMode === 'register')
                <label>¿Qué has comido?</label>
                <textarea
                    rows="3"
                    wire:model="quickText"
                    placeholder="Ej: Hoy he desayunado un café con leche y una tostada con tomate"
                    @disabled($quickLoading)
                ></textarea>

                <label>O sube una foto del plato</label>
                <input type="file" accept="image/*" wire:model="quickPhoto" @disabled($quickLoading)>
                <div wire:loading wire:target="quickPhoto" class="muted">Subiendo imagen…</div>
                @error('quickPhoto') <div class="error">{{ $message }}</div> @enderror

                <div class="nav-links" style="margin-bottom:1rem">
                    <button
                        type="button"
                        class="btn btn-primary"
                        wire:click="analyzeQuickEntry"
                        wire:loading.attr="disabled"
                        wire:target="analyzeQuickEntry"
                    >
                        <span wire:loading.remove wire:target="analyzeQuickEntry">Analizar con IA</span>
                        <span wire:loading wire:target="analyzeQuickEntry">Analizando…</span>
                    </button>
                </div>

                @if ($quickPreview)
                    <div class="card" style="margin-bottom:0;background:var(--accent-2)">
                        <div class="nav" style="margin:0 0 .5rem">
                            <strong>{{ $quickPreview['title'] }}</strong>
                            <span class="muted">{{ $quickPreview['meal_type_label'] ?? $quickPreview['meal_type'] }} · {{ \App\Support\Labels::date($date) }}</span>
                        </div>
                        @if (!empty($quickPreview['description']))
                            <p class="muted" style="margin-top:0">{{ $quickPreview['description'] }}</p>
                        @endif

                        <div class="grid grid-4" style="margin-bottom:.75rem">
                            <div><div class="muted">kcal</div><strong>{{ round($quickPreview['calories']) }}</strong></div>
                            <div><div class="muted">Proteína</div><strong>{{ round($quickPreview['protein_g'], 1) }} g</strong></div>
                            <div><div class="muted">Carbos</div><strong>{{ round($quickPreview['carbs_g'], 1) }} g</strong></div>
                            <div><div class="muted">Grasas</div><strong>{{ round($quickPreview['fat_g'], 1) }} g</strong></div>
                        </div>

                        @if (!empty($quickPreview['items']))
                            <h3 style="margin:.5rem 0;font-size:1rem">Ingredientes</h3>
                            @foreach ($quickPreview['items'] as $item)
                                <div class="meal-row">
                                    <div>
                                        <strong>{{ $item['name'] ?? 'Item' }}</strong>
                                        <div class="muted">{{ round($item['quantity_g'] ?? 0) }} g</div>
                                    </div>
                                    <div class="muted">{{ round($item['calories'] ?? 0) }} kcal</div>
                                </div>
                            @endforeach
                        @endif

                        @if (!empty($quickPreview['micros']))
                            <h3 style="margin:.75rem 0 .35rem;font-size:1rem">Micronutrientes</h3>
                            <div class="grid grid-4">
                                @foreach ($quickPreview['micros'] as $key => $value)
                                    <div>
                                        <div class="muted">{{ str_replace('_', ' ', $key) }}</div>
                                        <strong>{{ round($value, 1) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <label style="margin-top:.85rem">Tipo de comida</label>
                        <select wire:model="quickPreview.meal_type">
                            <option value="breakfast">Desayuno</option>
                            <option value="lunch">Comida / Almuerzo</option>
                            <option value="dinner">Cena</option>
                            <option value="snack">Snack</option>
                        </select>

                        <button type="button" class="btn btn-primary" wire:click="confirmQuickEntry" wire:loading.attr="disabled">
                            Confirmar y guardar
                        </button>
                    </div>
                @endif
            @else
                @if ($nutritionContext)
                    <div class="card" style="background:#f7faf8">
                        <strong>Contexto usado por la IA (3 días)</strong>
                        <p class="muted" style="margin:.35rem 0">
                            Objetivo:
                            {{ match($nutritionContext['goal'] ?? 'lose_weight') {
                                'gain_muscle' => 'ganar músculo',
                                'maintain' => 'mantenimiento',
                                default => 'perder peso',
                            } }}
                            · Comidas recientes: {{ count($nutritionContext['history_3_days'] ?? []) }}
                        </p>
                        @if (!empty($nutritionContext['likely_gaps']))
                            <p class="muted" style="margin:0">
                                Posibles déficits:
                                {{ collect($nutritionContext['likely_gaps'])->map(fn ($g) => str_replace('_', ' ', $g))->join(', ') }}
                            </p>
                        @endif
                    </div>
                @endif

                <label>¿Qué necesitas?</label>
                <textarea
                    rows="3"
                    wire:model="suggestText"
                    placeholder="Ej: Sugiéreme qué cenar mañana para completar vitaminas y minerales"
                    @disabled($quickLoading)
                ></textarea>
                @error('suggestText') <div class="error">{{ $message }}</div> @enderror

                <div class="chip-row">
                    <button type="button" class="chip" wire:click="$set('suggestText', 'Sugiéreme qué comer hoy para cenar')">Cena hoy</button>
                    <button type="button" class="chip" wire:click="$set('suggestText', 'Sugiéreme qué puedo comer mañana para completar mis vitaminas')">Vitaminas mañana</button>
                    <button type="button" class="chip" wire:click="$set('suggestText', 'Propón un almuerzo equilibrado para mañana evitando repetir ingredientes recientes')">Almuerzo variado</button>
                </div>

                <div class="nav-links" style="margin: .5rem 0 1rem">
                    <button
                        type="button"
                        class="btn btn-primary"
                        wire:click="requestSuggestions"
                        wire:loading.attr="disabled"
                        wire:target="requestSuggestions"
                    >
                        <span wire:loading.remove wire:target="requestSuggestions">Pedir sugerencias IA</span>
                        <span wire:loading wire:target="requestSuggestions">Pensando…</span>
                    </button>
                </div>

                @if ($suggestResult)
                    <div class="alert" style="margin-bottom:.85rem">{{ $suggestResult['summary'] ?? '' }}</div>
                    @if (!empty($suggestResult['nutrient_focus']))
                        <p class="muted">Enfoque: {{ collect($suggestResult['nutrient_focus'])->map(fn ($n) => str_replace('_', ' ', $n))->join(', ') }}</p>
                    @endif

                    @forelse (($suggestResult['suggestions'] ?? []) as $suggestion)
                        <div class="card" style="background:var(--accent-2)">
                            <div class="nav" style="margin:0 0 .4rem">
                                <strong>{{ $suggestion['title'] ?? 'Sugerencia' }}</strong>
                                <span class="muted">{{ $suggestion['meal_type_label'] ?? '' }} · {{ \App\Support\Labels::date($suggestion['target_date'] ?? null) }}</span>
                            </div>
                            <p class="muted" style="margin-top:0">{{ $suggestion['description'] ?? '' }}</p>
                            @if (!empty($suggestion['reason']))
                                <p style="margin:.35rem 0 .7rem"><em>{{ $suggestion['reason'] }}</em></p>
                            @endif
                            <div class="grid grid-4" style="margin-bottom:.75rem">
                                <div><div class="muted">kcal</div><strong>{{ round($suggestion['calories'] ?? 0) }}</strong></div>
                                <div><div class="muted">Proteína</div><strong>{{ round($suggestion['protein_g'] ?? 0, 1) }} g</strong></div>
                                <div><div class="muted">Carbos</div><strong>{{ round($suggestion['carbs_g'] ?? 0, 1) }} g</strong></div>
                                <div><div class="muted">Grasas</div><strong>{{ round($suggestion['fat_g'] ?? 0, 1) }} g</strong></div>
                            </div>
                            @if (!empty($suggestion['items']))
                                @foreach ($suggestion['items'] as $item)
                                    <div class="meal-row">
                                        <div>
                                            <strong>{{ $item['name'] ?? 'Item' }}</strong>
                                            <div class="muted">{{ round($item['quantity_g'] ?? 0) }} g</div>
                                        </div>
                                        <div class="muted">{{ round($item['calories'] ?? 0) }} kcal</div>
                                    </div>
                                @endforeach
                            @endif
                            <button
                                type="button"
                                class="btn btn-primary"
                                style="margin-top:.85rem"
                                wire:click="acceptSuggestion('{{ $suggestion['id'] }}')"
                                wire:loading.attr="disabled"
                            >
                                Aceptar e insertar en mi plan
                            </button>
                        </div>
                    @empty
                        <p class="muted">No quedan sugerencias pendientes.</p>
                    @endforelse
                @endif
            @endif
        </div>
    @endif
</div>
