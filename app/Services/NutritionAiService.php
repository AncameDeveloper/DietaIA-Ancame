<?php

namespace App\Services;

use App\Models\AiLog;
use App\Models\DietPlan;
use App\Models\Food;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class NutritionAiService
{
    public function analyzeText(User $user, string $description, string $mealType = 'lunch'): array
    {
        $foodContext = $this->localFoodContext($description);

        $prompt = <<<PROMPT
Eres un nutricionista. Estima la composición nutricional de esta comida en español.
Comida: {$description}
Tipo de comida: {$mealType}
Contexto de base local (si aplica): {$foodContext}

Responde SOLO JSON válido con este esquema:
{
  "title": "string",
  "items": [{"name":"string","quantity_g":number,"calories":number,"protein_g":number,"carbs_g":number,"fat_g":number}],
  "calories": number,
  "protein_g": number,
  "carbs_g": number,
  "fat_g": number,
  "fiber_g": number,
  "micros": {"vitamin_a_mcg":number,"vitamin_c_mg":number,"vitamin_d_mcg":number,"calcium_mg":number,"iron_mg":number,"magnesium_mg":number,"potassium_mg":number},
  "confidence": number
}
PROMPT;

        return $this->requestJson($user, 'analyze_text', $prompt, [], $description);
    }

    public function analyzePhoto(User $user, string $absolutePath, string $mealType = 'lunch', ?string $hint = null): array
    {
        $mime = mime_content_type($absolutePath) ?: 'image/jpeg';
        $base64 = base64_encode((string) file_get_contents($absolutePath));
        $hintText = $hint ? "Pista del usuario: {$hint}" : 'Sin pista adicional.';

        $prompt = <<<PROMPT
Analiza la foto de comida y estima nutrientes. {$hintText}
Tipo de comida: {$mealType}
Responde SOLO JSON válido con:
{
  "title": "string",
  "description": "string",
  "items": [{"name":"string","quantity_g":number,"calories":number,"protein_g":number,"carbs_g":number,"fat_g":number}],
  "calories": number,
  "protein_g": number,
  "carbs_g": number,
  "fat_g": number,
  "fiber_g": number,
  "micros": {"vitamin_a_mcg":number,"vitamin_c_mg":number,"vitamin_d_mcg":number,"calcium_mg":number,"iron_mg":number,"magnesium_mg":number,"potassium_mg":number},
  "confidence": number
}
PROMPT;

        return $this->requestJson($user, 'analyze_photo', $prompt, [
            [
                'inline_data' => [
                    'mime_type' => $mime,
                    'data' => $base64,
                ],
            ],
        ], $hint ?: 'comida en foto');
    }

    /**
     * Entrada rápida en lenguaje natural o foto: detecta una o varias comidas + nutrientes.
     *
     * @return array{meals: list<array<string, mixed>>}
     */
    public function analyzeQuickEntry(User $user, ?string $text = null, ?string $absolutePath = null): array
    {
        $foodContext = $this->localFoodContext($text ?? '');
        $hourHint = now()->format('H:i');

        $prompt = <<<PROMPT
Eres un asistente nutricional. Analiza la entrada del usuario y extrae TODAS las comidas distintas que menciona para registrar hoy.
Hora actual aproximada: {$hourHint}
Texto del usuario: {$text}
Contexto de base local (si aplica): {$foodContext}

Reglas OBLIGATORIAS:
1) Si el usuario menciona varias comidas (ej. "desayuné café, comí sardinas y cené un huevo"), debes devolver UNA entrada por cada comida.
2) SIEMPRE responde con un objeto JSON que contenga la clave "meals" (array). Aunque solo haya 1 comida, "meals" debe ser un array de 1 elemento.
3) Detecta meal_type por palabras (desayuno/almuerzo/comida/cena/snack/merienda) o, si no hay pista en esa comida, según la hora.
4) No fusiones comidas de distinto tipo en un solo objeto.

Responde SOLO JSON válido con este esquema:
{
  "meals": [
    {
      "meal_type": "breakfast|lunch|dinner|snack",
      "meal_type_label": "Desayuno|Comida|Cena|Snack",
      "title": "string",
      "description": "string",
      "items": [{"name":"string","quantity_g":number,"calories":number,"protein_g":number,"carbs_g":number,"fat_g":number}],
      "calories": number,
      "protein_g": number,
      "carbs_g": number,
      "fat_g": number,
      "fiber_g": number,
      "micros": {"vitamin_a_mcg":number,"vitamin_c_mg":number,"vitamin_d_mcg":number,"calcium_mg":number,"iron_mg":number,"magnesium_mg":number,"potassium_mg":number},
      "confidence": number
    }
  ]
}
PROMPT;

        $extraParts = [];
        if ($absolutePath && is_file($absolutePath)) {
            $mime = mime_content_type($absolutePath) ?: 'image/jpeg';
            $extraParts[] = [
                'inline_data' => [
                    'mime_type' => $mime,
                    'data' => base64_encode((string) file_get_contents($absolutePath)),
                ],
            ];
        }

        $result = $this->requestJson(
            $user,
            $absolutePath ? 'analyze_quick_photo' : 'analyze_quick_text',
            $prompt,
            $extraParts,
            $text ?: 'comida en foto'
        );

        return [
            'meals' => $this->normalizeMealsList($result, $text),
        ];
    }

    /**
     * Normaliza la respuesta de la IA a una lista de comidas.
     * Acepta: {meals:[...]}, un array raíz [...], o un único objeto legacy.
     * También admite claves en español (tipo, alimento, calorias).
     *
     * @return list<array<string, mixed>>
     */
    public function normalizeMealsList(array $result, ?string $text = null): array
    {
        $raw = [];

        if (isset($result['meals']) && is_array($result['meals'])) {
            $raw = $result['meals'];
        } elseif ($result !== [] && array_is_list($result) && is_array($result[0] ?? null)) {
            $raw = $result;
        } elseif (
            isset($result['title'])
            || isset($result['alimento'])
            || isset($result['calories'])
            || isset($result['calorias'])
            || isset($result['meal_type'])
            || isset($result['tipo'])
        ) {
            $raw = [$result];
        }

        $meals = [];
        foreach ($raw as $meal) {
            if (! is_array($meal)) {
                continue;
            }

            $hint = trim(($meal['title'] ?? $meal['alimento'] ?? '').' '.($meal['description'] ?? ''));
            $mealType = $this->normalizeMealType(
                $meal['meal_type'] ?? $meal['tipo'] ?? null,
                $hint !== '' ? $hint : $text
            );

            $meals[] = [
                'meal_type' => $mealType,
                'meal_type_label' => $meal['meal_type_label']
                    ?? $this->mealTypeLabelFromSpanish($meal['tipo'] ?? null)
                    ?? $this->mealTypeLabel($mealType),
                'title' => $meal['title'] ?? $meal['alimento'] ?? 'Comida',
                'description' => $meal['description'] ?? ($meal['alimento'] ?? $text),
                'items' => $meal['items'] ?? [],
                'calories' => (float) ($meal['calories'] ?? $meal['calorias'] ?? 0),
                'protein_g' => (float) ($meal['protein_g'] ?? $meal['proteinas'] ?? 0),
                'carbs_g' => (float) ($meal['carbs_g'] ?? $meal['carbohidratos'] ?? 0),
                'fat_g' => (float) ($meal['fat_g'] ?? $meal['grasas'] ?? 0),
                'fiber_g' => (float) ($meal['fiber_g'] ?? $meal['fibra'] ?? 0),
                'micros' => $meal['micros'] ?? [],
                'confidence' => $meal['confidence'] ?? null,
            ];
        }

        if ($meals === []) {
            $mealType = $this->normalizeMealType(null, $text);

            return [[
                'meal_type' => $mealType,
                'meal_type_label' => $this->mealTypeLabel($mealType),
                'title' => 'Comida',
                'description' => $text,
                'items' => [],
                'calories' => 0.0,
                'protein_g' => 0.0,
                'carbs_g' => 0.0,
                'fat_g' => 0.0,
                'fiber_g' => 0.0,
                'micros' => [],
                'confidence' => null,
            ]];
        }

        return $meals;
    }

    private function mealTypeLabelFromSpanish(mixed $tipo): ?string
    {
        if (! is_string($tipo) || $tipo === '') {
            return null;
        }

        $t = Str::lower(trim($tipo));

        return match (true) {
            str_contains($t, 'desayun') => 'Desayuno',
            str_contains($t, 'almuerzo') || $t === 'comida' => 'Comida',
            str_contains($t, 'cen') => 'Cena',
            str_contains($t, 'snack') || str_contains($t, 'meriend') => 'Snack',
            default => null,
        };
    }

    public function normalizeMealType(?string $type, ?string $text = null): string
    {
        $type = Str::lower(trim((string) $type));
        $allowed = ['breakfast', 'lunch', 'dinner', 'snack'];
        if (in_array($type, $allowed, true)) {
            return $type;
        }

        // Mapear etiquetas en español del modelo.
        $mapped = match (true) {
            str_contains($type, 'desayun') => 'breakfast',
            str_contains($type, 'almuerzo') || $type === 'comida' => 'lunch',
            str_contains($type, 'cen') => 'dinner',
            str_contains($type, 'snack') || str_contains($type, 'meriend') => 'snack',
            default => null,
        };
        if ($mapped !== null) {
            return $mapped;
        }

        $hay = Str::lower((string) $text);
        return match (true) {
            str_contains($hay, 'desayun') => 'breakfast',
            str_contains($hay, 'almuerzo') || preg_match('/\bcomida\b/u', $hay) === 1 => 'lunch',
            str_contains($hay, 'cen') => 'dinner',
            str_contains($hay, 'snack') || str_contains($hay, 'meriend') || str_contains($hay, 'tentempi') => 'snack',
            default => $this->mealTypeByHour(),
        };
    }

    public function mealTypeLabel(string $type): string
    {
        return match ($type) {
            'breakfast' => 'Desayuno',
            'lunch' => 'Comida',
            'dinner' => 'Cena',
            default => 'Snack',
        };
    }

    private function mealTypeByHour(): string
    {
        $hour = (int) now()->format('G');

        return match (true) {
            $hour < 11 => 'breakfast',
            $hour < 16 => 'lunch',
            $hour < 20 => 'snack',
            default => 'dinner',
        };
    }

    /**
     * Sugerencias de comidas según historial de 3 días, objetivo y huecos de micronutrientes.
     *
     * @param  list<array{role?: string, content?: string}>  $history
     */
    public function suggestBalancedMeals(User $user, string $request, array $history = []): array
    {
        $context = $this->buildRecentNutritionContext($user);
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        $history = collect($history)
            ->filter(fn ($row) => is_array($row) && filled($row['content'] ?? null))
            ->take(-8)
            ->map(fn ($row) => [
                'role' => in_array(($row['role'] ?? ''), ['user', 'assistant'], true) ? $row['role'] : 'user',
                'content' => Str::limit((string) $row['content'], 1200, ''),
            ])
            ->values()
            ->all();
        $historyJson = json_encode($history, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Eres un nutricionista clínico. El usuario pide recomendaciones de comida.
Petición actual: {$request}
Hoy es {$today}. Mañana es {$tomorrow}.
Contexto del usuario (perfil, objetivo, historial 3 días, micros e ingredientes recientes):
{$contextJson}

Historial reciente del chat de esta sesión (úsalo para preguntas de seguimiento; p. ej. sustituir un alimento de una sugerencia anterior):
{$historyJson}

Reglas obligatorias:
1) Usa el historial de los últimos 3 días para cubrir déficits de vitaminas/minerales y evitar repetir los mismos ingredientes principales.
2) Respeta el objetivo (lose_weight|maintain|gain_muscle) y calorías/macros objetivo.
3) Prioriza densidad nutricional, variedad y platos realistas en español.
4) Si pide "hoy" o "mañana" o un tipo de comida (cena/desayuno...), limítalo a eso; si no, ofrece 2-3 opciones útiles.
5) Si el mensaje es un seguimiento (cambiar, sustituir, adaptar una sugerencia previa), conserva el resto del plato y aplica solo el cambio pedido.

Responde SOLO JSON válido:
{
  "summary": "string breve con el razonamiento nutricional",
  "nutrient_focus": ["hierro","vitamina C", "..."],
  "suggestions": [
    {
      "id": "s1",
      "target_date": "YYYY-MM-DD",
      "meal_type": "breakfast|lunch|dinner|snack",
      "meal_type_label": "Desayuno|Comida|Cena|Snack",
      "title": "string",
      "description": "string",
      "reason": "por qué encaja con déficits/variedad/objetivo",
      "items": [{"name":"string","quantity_g":number,"calories":number,"protein_g":number,"carbs_g":number,"fat_g":number}],
      "calories": number,
      "protein_g": number,
      "carbs_g": number,
      "fat_g": number,
      "fiber_g": number,
      "micros": {"vitamin_a_mcg":number,"vitamin_c_mg":number,"vitamin_d_mcg":number,"calcium_mg":number,"iron_mg":number,"magnesium_mg":number,"potassium_mg":number}
    }
  ]
}
PROMPT;

        $result = $this->requestJson($user, 'suggest_balanced_meals', $prompt, [], $request);

        $suggestions = collect($result['suggestions'] ?? [])->values()->map(function ($item, $index) use ($request) {
            $mealType = $this->normalizeMealType($item['meal_type'] ?? null, ($item['title'] ?? '').' '.$request);
            $targetDate = $item['target_date'] ?? now()->toDateString();
            if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $targetDate)) {
                $hay = Str::lower($request.' '.($item['reason'] ?? ''));
                $targetDate = str_contains($hay, 'mañana') || str_contains($hay, 'manana')
                    ? now()->addDay()->toDateString()
                    : now()->toDateString();
            }

            return [
                'id' => (string) ($item['id'] ?? 's'.($index + 1)),
                'target_date' => $targetDate,
                'meal_type' => $mealType,
                'meal_type_label' => $item['meal_type_label'] ?? $this->mealTypeLabel($mealType),
                'title' => $item['title'] ?? 'Sugerencia',
                'description' => $item['description'] ?? '',
                'reason' => $item['reason'] ?? '',
                'items' => $item['items'] ?? [],
                'calories' => (float) ($item['calories'] ?? 0),
                'protein_g' => (float) ($item['protein_g'] ?? 0),
                'carbs_g' => (float) ($item['carbs_g'] ?? 0),
                'fat_g' => (float) ($item['fat_g'] ?? 0),
                'fiber_g' => (float) ($item['fiber_g'] ?? 0),
                'micros' => $item['micros'] ?? [],
            ];
        })->all();

        return [
            'summary' => $result['summary'] ?? 'Sugerencias basadas en tu historial y objetivo.',
            'nutrient_focus' => $result['nutrient_focus'] ?? ($context['likely_gaps'] ?? []),
            'suggestions' => $suggestions,
            'context' => [
                'days_analyzed' => 3,
                'goal' => $context['goal'] ?? null,
                'ingredients_recent' => $context['recent_ingredients'] ?? [],
                'likely_gaps' => $context['likely_gaps'] ?? [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildRecentNutritionContext(User $user): array
    {
        $user->loadMissing(['profile', 'activeDietAssignment.dietPlan']);
        $from = now()->subDays(2)->startOfDay();
        $to = now()->endOfDay();

        $meals = $user->meals()
            ->with('items')
            ->whereBetween('eaten_on', [$from->toDateString(), $to->toDateString()])
            ->where('confirmed', true)
            ->orderBy('eaten_on')
            ->orderBy('id')
            ->get();

        $micros = [];
        $macros = ['calories' => 0.0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 0.0, 'fiber_g' => 0.0];
        $ingredients = [];
        $history = [];

        foreach ($meals as $meal) {
            $history[] = [
                'date' => $meal->eaten_on?->toDateString(),
                'meal_type' => $meal->meal_type,
                'title' => $meal->title,
                'calories' => (float) $meal->calories,
                'protein_g' => (float) $meal->protein_g,
                'carbs_g' => (float) $meal->carbs_g,
                'fat_g' => (float) $meal->fat_g,
                'items' => $meal->items->pluck('name')->all(),
            ];

            foreach (['calories', 'protein_g', 'carbs_g', 'fat_g', 'fiber_g'] as $key) {
                $macros[$key] += (float) $meal->{$key};
            }
            foreach (($meal->micros ?? []) as $key => $value) {
                $micros[$key] = ($micros[$key] ?? 0) + (float) $value;
            }
            foreach ($meal->items as $item) {
                $ingredients[] = Str::lower((string) $item->name);
            }
            if ($meal->title) {
                $ingredients[] = Str::lower((string) $meal->title);
            }
        }

        $dailyTargets = [
            'vitamin_a_mcg' => 800,
            'vitamin_c_mg' => 80,
            'vitamin_d_mcg' => 15,
            'calcium_mg' => 1000,
            'iron_mg' => 14,
            'magnesium_mg' => 350,
            'potassium_mg' => 3500,
        ];

        $days = max(1, $meals->pluck('eaten_on')->map(fn ($d) => optional($d)->toDateString())->unique()->count());
        $likelyGaps = [];
        foreach ($dailyTargets as $key => $daily) {
            $avg = ($micros[$key] ?? 0) / $days;
            if ($avg < $daily * 0.7) {
                $likelyGaps[] = $key;
            }
        }

        return [
            'age' => $user->profile?->age,
            'sex' => $user->profile?->sex,
            'weight_kg' => $user->profile?->weight_kg,
            'height_cm' => $user->profile?->height_cm,
            'start_weight_kg' => $user->profile?->start_weight_kg,
            'target_weight_kg' => $user->profile?->target_weight_kg,
            'goal' => $user->profile?->goal ?? 'lose_weight',
            'calorie_target' => $user->profile?->calorie_target,
            'protein_target_g' => $user->profile?->protein_target_g,
            'carbs_target_g' => $user->profile?->carbs_target_g,
            'fat_target_g' => $user->profile?->fat_target_g,
            'restrictions' => $user->profile?->restrictions ?? [],
            'allergies' => $user->profile?->allergies ?? [],
            'diet_plan' => $user->activeDietAssignment?->dietPlan?->only(['name', 'slug', 'description', 'rules']),
            'history_3_days' => $history,
            'totals_3_days' => [
                'macros' => $macros,
                'micros' => collect($micros)->map(fn ($v) => round($v, 1))->all(),
            ],
            'recent_ingredients' => collect($ingredients)->unique()->values()->take(40)->all(),
            'likely_gaps' => $likelyGaps,
        ];
    }

    /**
     * Chat libre de nutricionista con contexto automático del usuario.
     *
     * @param  list<array{role?: string, content?: string}>  $history
     * @return array{reply: string, context: array<string, mixed>}
     */
    public function nutritionistChat(User $user, string $message, array $history = []): array
    {
        $context = $this->buildRecentNutritionContext($user);
        $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);
        $history = collect($history)
            ->filter(fn ($row) => is_array($row) && filled($row['content'] ?? null))
            ->take(-8)
            ->map(fn ($row) => [
                'role' => in_array(($row['role'] ?? ''), ['user', 'assistant'], true) ? $row['role'] : 'user',
                'content' => Str::limit((string) $row['content'], 1200, ''),
            ])
            ->values()
            ->all();
        $historyJson = json_encode($history, JSON_UNESCAPED_UNICODE);
        $safeMessage = Str::limit(trim($message), 2000, '');

        $prompt = <<<PROMPT
Eres el nutricionista clínico de DietaIA. Responde en español, con tono profesional, cercano y práctico.
Adapta SIEMPRE la respuesta al perfil, dieta activa, objetivos y comidas recientes del usuario.
No inventes datos clínicos ni sustituyas consejo médico; si hay riesgo o patología, recomienda consultar a un profesional.

Contexto automático del usuario (NO lo inventes; úsalo):
{$contextJson}

Historial reciente del chat (si existe):
{$historyJson}

Pregunta del usuario:
{$safeMessage}

Reglas:
1) Ten en cuenta edad, peso actual, peso objetivo, altura, plan de dieta activo y lo comido en los últimos 3 días.
2) Si pregunta qué comer, prioriza opciones realistas que respeten calorías/macros restantes y déficits de micros.
3) Si pregunta sobre ayuno, entrenamiento o ajustes, responde de forma concreta y segura.
4) Sé breve pero útil (máx. ~180 palabras salvo que pida detalle).

Responde SOLO JSON válido:
{
  "reply": "respuesta en texto para el usuario",
  "focus": ["tema1","tema2"]
}
PROMPT;

        $result = $this->requestJson($user, 'nutritionist_chat', $prompt, [], $safeMessage);
        $reply = trim((string) ($result['reply'] ?? ''));
        if ($reply === '') {
            $reply = 'Puedo ayudarte con dudas de nutrición adaptadas a tu perfil y dieta. Reformula la pregunta con un poco más de detalle.';
        }

        return [
            'reply' => $reply,
            'focus' => array_values(array_filter($result['focus'] ?? [])),
            'context' => $this->publicNutritionistContext($context),
            'disclaimer' => 'DietaIA ofrece orientación general y no sustituye consejo médico ni nutricional profesional.',
        ];
    }

    /**
     * Resumen seguro para UI (sin volcar todo el historial crudo).
     *
     * @param  array<string, mixed>|null  $context
     * @return array<string, mixed>
     */
    public function publicNutritionistContext(?array $context = null, ?User $user = null): array
    {
        $context ??= $user ? $this->buildRecentNutritionContext($user) : [];

        return [
            'age' => $context['age'] ?? null,
            'weight_kg' => $context['weight_kg'] ?? null,
            'target_weight_kg' => $context['target_weight_kg'] ?? null,
            'height_cm' => $context['height_cm'] ?? null,
            'goal' => $context['goal'] ?? null,
            'diet_name' => is_array($context['diet_plan'] ?? null)
                ? ($context['diet_plan']['name'] ?? 'Sin plan activo')
                : ($context['diet_name'] ?? 'Sin plan activo'),
            'diet_slug' => is_array($context['diet_plan'] ?? null)
                ? ($context['diet_plan']['slug'] ?? null)
                : ($context['diet_slug'] ?? null),
            'calorie_target' => $context['calorie_target'] ?? null,
            'meals_recent_count' => count($context['history_3_days'] ?? []),
            'likely_gaps' => $context['likely_gaps'] ?? [],
            'based_on_profile' => true,
        ];
    }

    public function progressTips(User $user, array $progressStats): array
    {
        $payload = json_encode($progressStats, JSON_UNESCAPED_UNICODE);
        $prompt = <<<PROMPT
Eres un coach nutricional. Genera consejos personalizados en español según el progreso de peso del usuario.
Datos: {$payload}

Responde SOLO JSON:
{
  "tips":[
    {"title":"string","body":"string","tone":"motivational|caution|practical"}
  ],
  "summary":"string breve del ritmo de progreso"
}
Incluye 3-4 tips concretos según velocidad de cambio, objetivo y rachas.
PROMPT;

        $result = $this->requestJson($user, 'progress_tips', $prompt, [], 'progreso peso');

        return [
            'summary' => $result['summary'] ?? 'Sigue constante: el progreso sostenible es el que se mantiene.',
            'tips' => $result['tips'] ?? [],
        ];
    }

    public function suggestDiet(User $user): array
    {
        $profile = $user->profile;
        // Incluir id y macros_ratio: sin id la asignación falla (diet_plan_id null).
        $plans = DietPlan::query()
            ->where('is_active', true)
            ->get(['id', 'slug', 'name', 'description', 'macros_ratio']);

        $profileJson = json_encode([
            'age' => $profile?->age,
            'sex' => $profile?->sex,
            'weight_kg' => $profile?->weight_kg,
            'height_cm' => $profile?->height_cm,
            'activity_level' => $profile?->activity_level,
            'goal' => $profile?->goal,
            'restrictions' => $profile?->restrictions,
            'allergies' => $profile?->allergies,
        ], JSON_UNESCAPED_UNICODE);

        // Solo datos útiles para el prompt (sin id interno).
        $plansJson = $plans->map(fn (DietPlan $p) => $p->only(['slug', 'name', 'description']))
            ->values()
            ->toJson(JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Sugiere el plan de dieta más adecuado para pérdida de peso saludable.
Perfil: {$profileJson}
Planes disponibles: {$plansJson}
Responde SOLO JSON:
{"recommended_slug":"string","reason":"string","alternatives":[{"slug":"string","reason":"string"}]}
PROMPT;

        $result = $this->requestJson($user, 'suggest_diet', $prompt);
        $slug = $result['recommended_slug'] ?? null;
        $plan = $plans->firstWhere('slug', $slug) ?? $plans->first();

        return [
            'recommended' => $plan,
            'reason' => $result['reason'] ?? 'Plan equilibrado para tu objetivo.',
            'alternatives' => $result['alternatives'] ?? [],
            'raw' => $result,
        ];
    }

    public function generateMenu(User $user, string $horizon = 'daily'): array
    {
        $profile = $user->profile;
        $assignment = $user->relationLoaded('activeDietAssignment')
            ? $user->activeDietAssignment
            : $user->activeDietAssignment()->with('dietPlan')->first();
        if ($assignment && ! $assignment->relationLoaded('dietPlan')) {
            $assignment->load('dietPlan');
        }
        $plan = $assignment?->dietPlan;

        $context = json_encode([
            'calorie_target' => $profile?->calorie_target,
            'protein_target_g' => $profile?->protein_target_g,
            'carbs_target_g' => $profile?->carbs_target_g,
            'fat_target_g' => $profile?->fat_target_g,
            'restrictions' => $profile?->restrictions,
            'allergies' => $profile?->allergies,
            'diet_plan' => $plan?->only(['slug', 'name', 'rules', 'macros_ratio']),
            'horizon' => $horizon,
        ], JSON_UNESCAPED_UNICODE);

        $days = $horizon === 'weekly' ? 7 : 1;

        $prompt = <<<PROMPT
Genera un menú {$horizon} en español para el usuario, respetando su plan y restricciones.
Contexto: {$context}
Responde SOLO JSON:
{
  "notes": "string",
  "days": [
    {
      "day": 1,
      "date_label": "string",
      "meals": [
        {
          "meal_type":"breakfast|lunch|dinner|snack",
          "title":"string",
          "description":"string",
          "calories":number,
          "protein_g":number,
          "carbs_g":number,
          "fat_g":number,
          "ingredients":[{"name":"string","quantity_g":number}]
        }
      ]
    }
  ]
}
Incluye exactamente {$days} día(s). Sé conciso en descriptions (máx. 12 palabras).
En cada comida incluye "ingredients" con los ingredientes principales a comprar (name + quantity_g).
PROMPT;

        return $this->requestJson($user, 'generate_menu_'.$horizon, $prompt);
    }

    public static function tipsCacheKey(int $userId): string
    {
        return 'dietaia:tips:user:'.$userId;
    }

    /** @return array{tips: array}|null */
    public static function cachedTips(int $userId): ?array
    {
        $cached = cache()->get(self::tipsCacheKey($userId));

        return is_array($cached) ? $cached : null;
    }

    public function tips(User $user, bool $forceRefresh = false): array
    {
        $cacheKey = self::tipsCacheKey((int) $user->id);
        $ttlMinutes = max(5, (int) config('services.gemini.cache_tips_minutes', 45));

        if (! $forceRefresh) {
            $cached = cache()->get($cacheKey);
            if (is_array($cached) && isset($cached['tips'])) {
                return $cached;
            }
        }

        $profile = $user->profile;
        $assignment = $user->relationLoaded('activeDietAssignment')
            ? $user->activeDietAssignment
            : $user->activeDietAssignment()->with('dietPlan')->first();
        if ($assignment && ! $assignment->relationLoaded('dietPlan')) {
            $assignment->load('dietPlan');
        }
        $recent = $user->meals()
            ->latest('eaten_on')
            ->limit(5)
            ->get(['title', 'calories', 'protein_g', 'carbs_g', 'fat_g', 'eaten_on']);

        $context = json_encode([
            'profile' => $profile?->only(['goal', 'calorie_target', 'activity_level', 'restrictions']),
            'diet' => $assignment?->dietPlan?->only(['name', 'slug']),
            'recent_meals' => $recent,
        ], JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
Da 5 consejos prácticos de alimentación en español, breves y accionables.
Contexto: {$context}
Responde SOLO JSON: {"tips":[{"title":"string","body":"string"}]}
Incluye un disclaimer de que no sustituye consejo médico.
PROMPT;

        $result = $this->requestJson($user, 'tips', $prompt);
        $payload = [
            'tips' => $result['tips'] ?? [],
        ];
        cache()->put($cacheKey, $payload, now()->addMinutes($ttlMinutes));

        return $payload;
    }

    private function localFoodContext(string $description): string
    {
        $terms = collect(preg_split('/[\s,;.+]+/u', Str::lower($description)) ?: [])
            ->filter(fn ($t) => mb_strlen($t) > 3)
            ->take(6);

        if ($terms->isEmpty()) {
            return 'ninguno';
        }

        $foods = Food::query()
            ->where(function ($q) use ($terms) {
                foreach ($terms as $term) {
                    $q->orWhere('name', 'like', '%'.$term.'%');
                }
            })
            ->limit(8)
            ->get(['name', 'calories', 'protein_g', 'carbs_g', 'fat_g', 'micros']);

        return $foods->isEmpty() ? 'ninguno' : $foods->toJson(JSON_UNESCAPED_UNICODE);
    }

    private function requestJson(User $user, string $action, string $prompt, array $extraParts = [], ?string $fallbackHint = null): array
    {
        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            return $this->logAndReturnFallback($user, $action, $fallbackHint ?? $prompt, 'fallback_no_key');
        }

        $preferred = config('services.gemini.model', 'gemini-flash-latest');
        // Pocos fallbacks: evita cascadas de varios minutos si un modelo falla.
        $models = array_values(array_unique(array_filter([
            $preferred,
            'gemini-flash-latest',
            'gemini-2.0-flash',
        ])));

        $parts = array_merge([['text' => $prompt]], $extraParts);
        $lastStatus = null;
        $lastBody = null;
        $timeout = max(10, (int) config('services.gemini.timeout', 28));

        foreach ($models as $model) {
            try {
                $response = Http::timeout($timeout)
                    ->connectTimeout(8)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post(
                        "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}",
                        [
                            'contents' => [
                                ['role' => 'user', 'parts' => $parts],
                            ],
                            'generationConfig' => [
                                'temperature' => 0.3,
                                'responseMimeType' => 'application/json',
                            ],
                        ]
                    );
            } catch (\Throwable $e) {
                $lastStatus = 0;
                $lastBody = $e->getMessage();
                continue;
            }

            $lastStatus = $response->status();
            $lastBody = $response->body();

            if (! $response->successful()) {
                continue;
            }

            $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');

            try {
                $decoded = $this->decodeJson($text);
            } catch (\Throwable) {
                continue;
            }

            AiLog::create([
                'user_id' => $user->id,
                'action' => $action,
                'request_meta' => ['model' => $model],
                'response_raw' => $text,
                'success' => true,
            ]);

            return $decoded;
        }

        AiLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'request_meta' => [
                'status' => $lastStatus,
                'mode' => 'fallback_after_api_error',
                'models_tried' => $models,
            ],
            'response_raw' => $lastBody,
            'success' => false,
        ]);

        // No romper la UX si Gemini está sin cuota / caída: estimación local.
        return $this->fallbackPayload($action, $fallbackHint ?? $prompt);
    }

    private function logAndReturnFallback(User $user, string $action, string $hint, string $mode): array
    {
        $fallback = $this->fallbackPayload($action, $hint);
        AiLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'request_meta' => ['mode' => $mode],
            'response_raw' => json_encode($fallback),
            'success' => true,
        ]);

        return $fallback;
    }

    private function decodeJson(string $text): array
    {
        $clean = trim($text);
        $clean = preg_replace('/^```json\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/^```\s*/', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;

        $decoded = json_decode($clean, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('La IA devolvió una respuesta inválida.');
        }

        return $decoded;
    }

    private function estimateFromLocalFoods(string $description): array
    {
        $terms = collect(preg_split('/[\s,;.+]+/u', Str::lower($description)) ?: [])
            ->filter(fn ($t) => mb_strlen($t) > 2)
            ->unique()
            ->take(8)
            ->values();

        $matched = collect();
        if ($terms->isNotEmpty()) {
            $matched = Food::query()
                ->where(function ($q) use ($terms) {
                    foreach ($terms as $term) {
                        $q->orWhere('name', 'like', '%'.$term.'%');
                    }
                })
                ->limit(5)
                ->get();
        }

        if ($matched->isEmpty()) {
            return [
                'title' => Str::limit($description, 60) ?: 'Comida estimada',
                'description' => 'Estimación local (Gemini no disponible o sin cuota).',
                'items' => [[
                    'name' => 'Porción estimada',
                    'quantity_g' => 250,
                    'calories' => 420,
                    'protein_g' => 22,
                    'carbs_g' => 38,
                    'fat_g' => 16,
                ]],
                'calories' => 420,
                'protein_g' => 22,
                'carbs_g' => 38,
                'fat_g' => 16,
                'fiber_g' => 5,
                'micros' => [
                    'vitamin_a_mcg' => 180,
                    'vitamin_c_mg' => 25,
                    'vitamin_d_mcg' => 1,
                    'calcium_mg' => 100,
                    'iron_mg' => 3,
                    'magnesium_mg' => 70,
                    'potassium_mg' => 500,
                ],
                'confidence' => 0.35,
            ];
        }

        $items = [];
        $totals = ['calories' => 0.0, 'protein_g' => 0.0, 'carbs_g' => 0.0, 'fat_g' => 0.0, 'fiber_g' => 0.0];
        $micros = [];
        $portion = 120.0;

        foreach ($matched->take(5) as $food) {
            $factor = $portion / max(1, (float) $food->serving_g);
            $item = [
                'name' => $food->name,
                'quantity_g' => $portion,
                'calories' => round((float) $food->calories * $factor, 1),
                'protein_g' => round((float) $food->protein_g * $factor, 1),
                'carbs_g' => round((float) $food->carbs_g * $factor, 1),
                'fat_g' => round((float) $food->fat_g * $factor, 1),
            ];
            $items[] = $item;
            foreach (['calories', 'protein_g', 'carbs_g', 'fat_g'] as $k) {
                $totals[$k] += $item[$k];
            }
            $totals['fiber_g'] += (float) $food->fiber_g * $factor;
            foreach (($food->micros ?? []) as $mk => $mv) {
                $micros[$mk] = ($micros[$mk] ?? 0) + ((float) $mv * $factor);
            }
        }

        return [
            'title' => Str::limit($description, 60) ?: 'Comida estimada',
            'description' => 'Estimación desde base local (Gemini no disponible o sin cuota).',
            'items' => $items,
            'calories' => round($totals['calories'], 1),
            'protein_g' => round($totals['protein_g'], 1),
            'carbs_g' => round($totals['carbs_g'], 1),
            'fat_g' => round($totals['fat_g'], 1),
            'fiber_g' => round($totals['fiber_g'], 1),
            'micros' => collect($micros)->map(fn ($v) => round($v, 1))->all(),
            'confidence' => 0.55,
        ];
    }

    private function fallbackPayload(string $action, string $prompt): array
    {
        return match (true) {
            str_starts_with($action, 'analyze_quick') => [
                'meals' => [
                    array_merge(
                        $this->estimateFromLocalFoods($prompt),
                        [
                            'meal_type' => $this->normalizeMealType(null, $prompt),
                            'meal_type_label' => $this->mealTypeLabel($this->normalizeMealType(null, $prompt)),
                        ]
                    ),
                ],
            ],
            str_starts_with($action, 'analyze') => array_merge(
                $this->estimateFromLocalFoods($prompt),
                [
                    'meal_type' => $this->normalizeMealType(null, $prompt),
                    'meal_type_label' => $this->mealTypeLabel($this->normalizeMealType(null, $prompt)),
                ]
            ),
            $action === 'suggest_diet' => [
                'recommended_slug' => 'deficit-calorico',
                'reason' => 'Déficit calórico moderado, sostenible y adaptable (modo sin API key).',
                'alternatives' => [
                    ['slug' => 'alta-proteina', 'reason' => 'Ayuda a preservar músculo en pérdida de peso.'],
                    ['slug' => 'mediterranea', 'reason' => 'Patrón equilibrado y fácil de mantener.'],
                ],
            ],
            $action === 'nutritionist_chat' => [
                'reply' => 'Con la información de tu perfil y tu plan activo, prioriza comidas ricas en proteína y vegetales, ajusta las porciones a tu objetivo calórico y evita repetir los mismos platos varios días seguidos. Si tienes una duda concreta (cena, ayuno, entrenamiento…), plantéamela con más detalle.',
                'focus' => ['perfil', 'dieta', 'historial'],
            ],
            $action === 'progress_tips' => [
                'summary' => 'Vas por buen camino. Mantén el déficit moderado y la constancia semanal.',
                'tips' => [
                    ['title' => 'Ritmo saludable', 'body' => 'Una pérdida de 0.3–0.7 kg/semana suele ser sostenible.', 'tone' => 'practical'],
                    ['title' => 'Proteína diaria', 'body' => 'Prioriza proteína en cada comida para preservar músculo.', 'tone' => 'motivational'],
                    ['title' => 'Pesa con método', 'body' => 'Registra el peso a la misma hora, idealmente por la mañana.', 'tone' => 'practical'],
                    ['title' => 'Disclaimer', 'body' => 'DietaIA no sustituye consejo médico profesional.', 'tone' => 'caution'],
                ],
            ],
            $action === 'suggest_balanced_meals' => [
                'summary' => 'Sugerencias locales para equilibrar micros y variedad (Gemini no disponible).',
                'nutrient_focus' => ['iron_mg', 'vitamin_c_mg', 'calcium_mg'],
                'suggestions' => [
                    [
                        'id' => 's1',
                        'target_date' => now()->toDateString(),
                        'meal_type' => 'dinner',
                        'meal_type_label' => 'Cena',
                        'title' => 'Salmón con brócoli y quinoa',
                        'description' => 'Salmón al horno, brócoli al vapor y quinoa.',
                        'reason' => 'Aporta vitamina D, proteína y fibra sin repetir ingredientes típicos del histórico.',
                        'items' => [
                            ['name' => 'Salmón', 'quantity_g' => 150, 'calories' => 312, 'protein_g' => 30, 'carbs_g' => 0, 'fat_g' => 20],
                            ['name' => 'Brócoli', 'quantity_g' => 150, 'calories' => 51, 'protein_g' => 4, 'carbs_g' => 10, 'fat_g' => 1],
                            ['name' => 'Quinoa cocida', 'quantity_g' => 120, 'calories' => 144, 'protein_g' => 5, 'carbs_g' => 25, 'fat_g' => 2],
                        ],
                        'calories' => 507,
                        'protein_g' => 39,
                        'carbs_g' => 35,
                        'fat_g' => 23,
                        'fiber_g' => 7,
                        'micros' => [
                            'vitamin_d_mcg' => 12,
                            'vitamin_c_mg' => 90,
                            'iron_mg' => 3,
                            'magnesium_mg' => 90,
                            'potassium_mg' => 700,
                            'calcium_mg' => 80,
                            'vitamin_a_mcg' => 120,
                        ],
                    ],
                    [
                        'id' => 's2',
                        'target_date' => now()->addDay()->toDateString(),
                        'meal_type' => 'lunch',
                        'meal_type_label' => 'Comida',
                        'title' => 'Lentejas con espinacas y huevo',
                        'description' => 'Guiso suave de lentejas, espinacas y huevo cocido.',
                        'reason' => 'Refuerza hierro, folato y proteína vegetal con buena densidad nutricional.',
                        'items' => [
                            ['name' => 'Lentejas cocidas', 'quantity_g' => 200, 'calories' => 232, 'protein_g' => 18, 'carbs_g' => 40, 'fat_g' => 1],
                            ['name' => 'Espinacas', 'quantity_g' => 100, 'calories' => 23, 'protein_g' => 3, 'carbs_g' => 4, 'fat_g' => 0],
                            ['name' => 'Huevo', 'quantity_g' => 100, 'calories' => 155, 'protein_g' => 13, 'carbs_g' => 1, 'fat_g' => 11],
                        ],
                        'calories' => 410,
                        'protein_g' => 34,
                        'carbs_g' => 45,
                        'fat_g' => 12,
                        'fiber_g' => 12,
                        'micros' => [
                            'iron_mg' => 7,
                            'vitamin_a_mcg' => 450,
                            'vitamin_c_mg' => 25,
                            'calcium_mg' => 140,
                            'magnesium_mg' => 80,
                            'potassium_mg' => 800,
                            'vitamin_d_mcg' => 2,
                        ],
                    ],
                ],
            ],
            str_starts_with($action, 'generate_menu') => [
                'notes' => 'Menú de ejemplo. Configura GEMINI_API_KEY para menús personalizados con IA.',
                'days' => collect(range(1, str_contains($action, 'weekly') ? 7 : 1))->map(function ($day) {
                    return [
                        'day' => $day,
                        'date_label' => 'Día '.$day,
                        'meals' => [
                            ['meal_type' => 'breakfast', 'title' => 'Yogur griego con frutos rojos', 'description' => 'Yogur natural, fresas y nueces', 'calories' => 320, 'protein_g' => 22, 'carbs_g' => 28, 'fat_g' => 12, 'ingredients' => [['name' => 'Yogur griego', 'quantity_g' => 150], ['name' => 'Fresas', 'quantity_g' => 80], ['name' => 'Nueces', 'quantity_g' => 20]]],
                            ['meal_type' => 'lunch', 'title' => 'Pollo a la plancha con quinoa', 'description' => 'Pechuga, quinoa y ensalada', 'calories' => 520, 'protein_g' => 42, 'carbs_g' => 45, 'fat_g' => 14, 'ingredients' => [['name' => 'Pechuga de pollo', 'quantity_g' => 150], ['name' => 'Quinoa', 'quantity_g' => 80], ['name' => 'Ensalada mixta', 'quantity_g' => 120]]],
                            ['meal_type' => 'dinner', 'title' => 'Salmón con verduras', 'description' => 'Salmón al horno y brócoli', 'calories' => 480, 'protein_g' => 36, 'carbs_g' => 18, 'fat_g' => 28, 'ingredients' => [['name' => 'Salmón', 'quantity_g' => 150], ['name' => 'Brócoli', 'quantity_g' => 150]]],
                            ['meal_type' => 'snack', 'title' => 'Manzana y almendras', 'description' => '1 manzana + 10 almendras', 'calories' => 180, 'protein_g' => 5, 'carbs_g' => 22, 'fat_g' => 8, 'ingredients' => [['name' => 'Manzana', 'quantity_g' => 150], ['name' => 'Almendras', 'quantity_g' => 20]]],
                        ],
                    ];
                })->all(),
            ],
            default => [
                'tips' => [
                    ['title' => 'Prioriza proteína', 'body' => 'Incluye proteína magra en cada comida para saciedad.'],
                    ['title' => 'Hidratación', 'body' => 'Bebe agua a lo largo del día; a veces sed se confunde con hambre.'],
                    ['title' => 'Verduras primero', 'body' => 'Empieza el plato con verduras para volumen y micronutrientes.'],
                    ['title' => 'Come consciente', 'body' => 'Evita pantallas en la comida para no comer de más.'],
                    ['title' => 'Disclaimer', 'body' => 'DietaIA no sustituye consejo médico ni nutricional profesional.'],
                ],
            ],
        };
    }
}
