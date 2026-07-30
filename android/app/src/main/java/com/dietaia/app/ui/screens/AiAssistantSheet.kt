package com.dietaia.app.ui.screens

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.rememberLazyListState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.Send
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.AssistChip
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.unit.dp
import com.dietaia.app.data.MealSuggestionDto
import com.dietaia.app.data.NutritionistChatMessageDto
import com.dietaia.app.data.NutritionistContextDto

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)
@Composable
fun AiAssistantSheet(
    visible: Boolean,
    loading: Boolean,
    busyLabel: String? = null,
    message: String?,
    error: String?,
    nutritionistContext: NutritionistContextDto?,
    nutritionistMessages: List<NutritionistChatMessageDto>,
    mealSuggestions: List<MealSuggestionDto>,
    mealSuggestionSummary: String?,
    onDismiss: () -> Unit,
    onRegisterMeal: (description: String) -> Unit,
    onOpenMeals: () -> Unit,
    onLoadNutritionistContext: () -> Unit,
    onAskNutritionist: (String) -> Unit,
    onMealSuggestionPrompt: (String) -> Unit,
    onClearNutritionist: () -> Unit,
) {
    if (!visible) return

    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    var tab by remember { mutableStateOf("register") }
    var description by remember { mutableStateOf("") }
    var chatInput by remember { mutableStateOf("") }
    val listState = rememberLazyListState()

    LaunchedEffect(visible, tab) {
        if (visible && tab == "nutritionist") {
            onLoadNutritionistContext()
        }
    }
    LaunchedEffect(nutritionistMessages.size) {
        if (nutritionistMessages.isNotEmpty()) {
            listState.animateScrollToItem(nutritionistMessages.lastIndex)
        }
    }

    ModalBottomSheet(
        onDismissRequest = onDismiss,
        sheetState = sheetState,
    ) {
        Column(
            modifier = Modifier
                .fillMaxWidth()
                .padding(horizontal = 20.dp)
                .padding(bottom = 28.dp),
            verticalArrangement = Arrangement.spacedBy(10.dp),
        ) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically,
            ) {
                Text("Asistente IA", style = MaterialTheme.typography.headlineSmall)
                if (tab == "nutritionist") {
                    TextButton(
                        onClick = onClearNutritionist,
                        enabled = !loading,
                    ) {
                        Text("🔄 Nueva consulta")
                    }
                }
            }
            Text(
                "Registra comidas o consulta a tu nutricionista personalizado.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )

            Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                FilterChip(
                    selected = tab == "register",
                    onClick = {
                        if (tab == "nutritionist") onClearNutritionist()
                        tab = "register"
                    },
                    label = { Text("Registrar") },
                )
                FilterChip(
                    selected = tab == "nutritionist",
                    onClick = { tab = "nutritionist" },
                    label = { Text("Nutricionista IA") },
                )
            }

            AiBusyBanner(loading = loading, label = busyLabel)

            if (tab == "register") {
                OutlinedTextField(
                    value = description,
                    onValueChange = { description = it },
                    label = { Text("¿Qué has comido?") },
                    modifier = Modifier.fillMaxWidth(),
                    minLines = 2,
                    enabled = !loading,
                )
                Button(
                    onClick = {
                        val text = description.trim()
                        if (text.isNotBlank()) {
                            description = ""
                            onRegisterMeal(text)
                        }
                    },
                    enabled = !loading && description.isNotBlank(),
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Text(if (loading) (busyLabel ?: "Analizando…") else "Registrar con IA")
                }
                OutlinedButton(
                    onClick = onOpenMeals,
                    enabled = !loading,
                    modifier = Modifier.fillMaxWidth(),
                ) {
                    Text("Ir a registrar comida")
                }
            } else {
                NutritionistContextBanner(context = nutritionistContext)

                Text("Consultas rápidas", style = MaterialTheme.typography.titleSmall)
                FlowRow(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
                    listOf(
                        "¿Qué puedo cenar hoy si voy justo de calorías?",
                        "Sugiéreme qué comer hoy para cenar",
                        "¿Cómo encaja el ayuno con mi entrenamiento?",
                        "Propón un almuerzo equilibrado para mañana",
                    ).forEach { chip ->
                        AssistChip(
                            onClick = {
                                chatInput = ""
                                if (chip.contains("Sugiéreme", ignoreCase = true) ||
                                    chip.contains("Propón", ignoreCase = true)
                                ) {
                                    onMealSuggestionPrompt(chip)
                                } else {
                                    onAskNutritionist(chip)
                                }
                            },
                            enabled = !loading,
                            label = { Text(chip, maxLines = 2) },
                        )
                    }
                }

                if (nutritionistMessages.isEmpty()) {
                    Card(
                        colors = CardDefaults.cardColors(
                            containerColor = MaterialTheme.colorScheme.surfaceVariant.copy(alpha = 0.55f),
                        ),
                        modifier = Modifier.fillMaxWidth(),
                    ) {
                        Text(
                            "Pregunta lo que quieras: cenas ligeras, micros, ayuno, entrenamiento… " +
                                "La respuesta usará tu perfil y dieta actuales.",
                            modifier = Modifier.padding(12.dp),
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                } else {
                    LazyColumn(
                        state = listState,
                        modifier = Modifier
                            .fillMaxWidth()
                            .heightIn(max = 280.dp),
                        verticalArrangement = Arrangement.spacedBy(8.dp),
                    ) {
                        items(nutritionistMessages) { msg ->
                            ChatBubble(message = msg)
                        }
                    }
                }

                if (mealSuggestions.isNotEmpty()) {
                    mealSuggestionSummary?.let {
                        Text(it, style = MaterialTheme.typography.bodySmall)
                    }
                    mealSuggestions.take(3).forEach { suggestion ->
                        MealSuggestionCard(suggestion)
                    }
                }

                Row(
                    modifier = Modifier.fillMaxWidth(),
                    verticalAlignment = Alignment.CenterVertically,
                    horizontalArrangement = Arrangement.spacedBy(8.dp),
                ) {
                    OutlinedTextField(
                        value = chatInput,
                        onValueChange = { chatInput = it },
                        modifier = Modifier.weight(1f),
                        label = { Text("Tu duda de nutrición") },
                        minLines = 1,
                        maxLines = 4,
                        enabled = !loading,
                    )
                    IconButton(
                        onClick = {
                            val text = chatInput.trim()
                            if (text.isNotBlank()) {
                                chatInput = ""
                                onAskNutritionist(text)
                            }
                        },
                        enabled = !loading && chatInput.isNotBlank(),
                    ) {
                        Icon(Icons.AutoMirrored.Filled.Send, contentDescription = "Enviar")
                    }
                }

                Text(
                    "Puedes hacer preguntas de seguimiento (ej. «¿Puedo cambiar el salmón por merluza?»).",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }

            if (message != null && tab == "register") {
                Text(message, color = MaterialTheme.colorScheme.primary)
            }
            if (error != null) {
                Text(error, color = MaterialTheme.colorScheme.error)
            }

            Spacer(Modifier.height(4.dp))
            TextButton(onClick = onDismiss, modifier = Modifier.fillMaxWidth()) {
                Text("Cerrar")
            }
        }
    }
}

@Composable
private fun NutritionistContextBanner(context: NutritionistContextDto?) {
    val diet = context?.diet_name ?: "tu dieta actual"
    val weight = context?.weight_kg?.let { "${it} kg" } ?: "—"
    val target = context?.target_weight_kg?.let { "${it} kg" }
    val meals = context?.meals_recent_count ?: 0

    Card(
        modifier = Modifier.fillMaxWidth(),
        colors = CardDefaults.cardColors(
            containerColor = MaterialTheme.colorScheme.secondaryContainer,
        ),
    ) {
        Row(
            modifier = Modifier.padding(12.dp),
            horizontalArrangement = Arrangement.spacedBy(10.dp),
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Icon(Icons.Default.Person, contentDescription = null)
            Column(modifier = Modifier.weight(1f)) {
                Text(
                    "Responde con tu perfil y dieta",
                    style = MaterialTheme.typography.titleSmall,
                )
                Text(
                    buildString {
                        append(diet)
                        append(" · peso $weight")
                        if (target != null) append(" → $target")
                        append(" · $meals comidas recientes")
                        context?.calorie_target?.let { append(" · $it kcal") }
                    },
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSecondaryContainer,
                )
            }
        }
    }
}

@Composable
private fun ChatBubble(message: NutritionistChatMessageDto) {
    val mine = message.role == "user"
    val bg = if (mine) {
        MaterialTheme.colorScheme.primaryContainer
    } else {
        MaterialTheme.colorScheme.surfaceVariant
    }
    Box(
        modifier = Modifier.fillMaxWidth(),
        contentAlignment = if (mine) Alignment.CenterEnd else Alignment.CenterStart,
    ) {
        Text(
            text = message.content,
            modifier = Modifier
                .fillMaxWidth(0.92f)
                .clip(RoundedCornerShape(14.dp))
                .background(bg)
                .padding(horizontal = 12.dp, vertical = 10.dp),
            style = MaterialTheme.typography.bodyMedium,
        )
    }
}

@Composable
private fun MealSuggestionCard(suggestion: MealSuggestionDto) {
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(12.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
            Text(suggestion.title ?: "Sugerencia", style = MaterialTheme.typography.titleSmall)
            val meta = listOfNotNull(
                suggestion.meal_type_label,
                suggestion.target_date?.let { formatEsDate(it) },
                suggestion.calories?.let { "${it.toInt()} kcal" },
            ).joinToString(" · ")
            if (meta.isNotBlank()) {
                Text(meta, style = MaterialTheme.typography.labelMedium, color = MaterialTheme.colorScheme.primary)
            }
            suggestion.description?.takeIf { it.isNotBlank() }?.let {
                Text(it, style = MaterialTheme.typography.bodySmall)
            }
            suggestion.reason?.takeIf { it.isNotBlank() }?.let {
                Text(it, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
            }
        }
    }
}
