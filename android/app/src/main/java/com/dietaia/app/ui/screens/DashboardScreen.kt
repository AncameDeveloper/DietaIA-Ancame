package com.dietaia.app.ui.screens

import androidx.compose.foundation.clickable
import androidx.compose.foundation.horizontalScroll
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowLeft
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.automirrored.filled.ShowChart
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material.icons.filled.Info
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DatePicker
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberDatePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.dietaia.app.data.DashboardResponse
import com.dietaia.app.data.MicronutrientsResponse
import com.dietaia.app.ui.EsLabels
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneOffset
import kotlin.math.min

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DashboardScreen(
    state: DashboardResponse?,
    selectedDate: String,
    micronutrients: MicronutrientsResponse?,
    microRange: String,
    microGroup: String,
    loading: Boolean,
    error: String?,
    softNotice: String? = null,
    onRefresh: () -> Unit,
    onPrevDay: () -> Unit,
    onNextDay: () -> Unit,
    onPickDate: (String) -> Unit,
    onOpenProgress: () -> Unit,
    onMicroRange: (String) -> Unit,
    onMicroGroup: (String) -> Unit,
    onDeleteMeal: (Int) -> Unit,
) {
    var showMicroInfo by remember { mutableStateOf(false) }
    var showDatePicker by remember { mutableStateOf(false) }
    val isToday = selectedDate == LocalDate.now().toString()
    val disclaimer = state?.disclaimer
        ?: "DietaIA ofrece orientación general y no sustituye consejo médico ni nutricional profesional."

    Column(modifier = Modifier.fillMaxSize().padding(horizontal = 16.dp, vertical = 8.dp)) {
        Text(
            state?.diet?.name ?: "Sin plan",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Spacer(modifier = Modifier.height(4.dp))

        Row(
            modifier = Modifier.fillMaxWidth(),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.SpaceBetween,
        ) {
            IconButton(onClick = onPrevDay) {
                Icon(Icons.AutoMirrored.Filled.KeyboardArrowLeft, contentDescription = "Día anterior")
            }
            Column(
                horizontalAlignment = Alignment.CenterHorizontally,
                modifier = Modifier
                    .weight(1f)
                    .clickable { showDatePicker = true },
            ) {
                Text(
                    formatEsDate(selectedDate),
                    style = MaterialTheme.typography.titleLarge,
                )
                Text(
                    if (isToday) "Hoy · toca para elegir fecha" else "Toca para elegir fecha",
                    style = MaterialTheme.typography.labelSmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            IconButton(onClick = onNextDay, enabled = !isToday) {
                Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, contentDescription = "Día siguiente")
            }
        }

        if (loading && state == null) {
            Spacer(modifier = Modifier.height(24.dp))
            CircularProgressIndicator(modifier = Modifier.align(Alignment.CenterHorizontally))
            return
        }
        if (error != null) {
            Text(error, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall)
        }
        if (softNotice != null) {
            Text(
                softNotice,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
                style = MaterialTheme.typography.bodySmall,
            )
        }

        LazyColumn(modifier = Modifier.weight(1f)) {
            item {
                Spacer(modifier = Modifier.height(8.dp))
                Card(
                    onClick = onOpenProgress,
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(
                        containerColor = MaterialTheme.colorScheme.secondaryContainer,
                    ),
                ) {
                    Row(
                        modifier = Modifier.fillMaxWidth().padding(14.dp),
                        verticalAlignment = Alignment.CenterVertically,
                        horizontalArrangement = Arrangement.spacedBy(12.dp),
                    ) {
                        Icon(Icons.AutoMirrored.Filled.ShowChart, contentDescription = null)
                        Column(modifier = Modifier.weight(1f)) {
                            Text("Mi Progreso", style = MaterialTheme.typography.titleMedium)
                            Text(
                                "Gráficas e historial de peso",
                                style = MaterialTheme.typography.bodySmall,
                                color = MaterialTheme.colorScheme.onSecondaryContainer,
                            )
                        }
                    }
                }
                Spacer(modifier = Modifier.height(12.dp))
            }

            if (state != null) {
                item {
                    MetricCard("Calorías", state.summary?.calories ?: 0.0, state.targets?.calories)
                    MetricCard("Proteína", state.summary?.protein_g ?: 0.0, state.targets?.protein_g)
                    MetricCard("Carbos", state.summary?.carbs_g ?: 0.0, state.targets?.carbs_g)
                    MetricCard("Grasas", state.summary?.fat_g ?: 0.0, state.targets?.fat_g)
                    Spacer(modifier = Modifier.height(12.dp))
                }

                item {
                    Card(modifier = Modifier.fillMaxWidth().padding(bottom = 8.dp)) {
                        Column(modifier = Modifier.padding(12.dp)) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Text(
                                    "Micronutrientes",
                                    style = MaterialTheme.typography.titleMedium,
                                    modifier = Modifier.weight(1f),
                                )
                                IconButton(onClick = { showMicroInfo = true }) {
                                    Icon(Icons.Default.Info, contentDescription = "Información")
                                }
                            }
                            Row(
                                modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                            ) {
                                FilterChip(
                                    selected = microRange == "today",
                                    onClick = { onMicroRange("today") },
                                    label = { Text("Hoy") },
                                )
                                FilterChip(
                                    selected = microRange == "7days",
                                    onClick = { onMicroRange("7days") },
                                    label = { Text("Promedio 7 días") },
                                )
                            }
                            Spacer(modifier = Modifier.height(6.dp))
                            Row(
                                modifier = Modifier.fillMaxWidth().horizontalScroll(rememberScrollState()),
                                horizontalArrangement = Arrangement.spacedBy(8.dp),
                            ) {
                                val groups = micronutrients?.groups?.ifEmpty { defaultMicroGroups() }
                                    ?: defaultMicroGroups()
                                groups.forEach { (key, label) ->
                                    FilterChip(
                                        selected = microGroup == key,
                                        onClick = { onMicroGroup(key) },
                                        label = { Text(label) },
                                    )
                                }
                            }
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                when {
                                    micronutrients == null && softNotice != null ->
                                        "Micronutrientes no disponibles de momento."
                                    microRange == "7days" ->
                                        "Promedio de ${micronutrients?.days_counted ?: 0} día(s) con registros"
                                    else -> "Totales del día · CDR diaria"
                                },
                                style = MaterialTheme.typography.labelSmall,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                    }
                }

                items(micronutrients?.items.orEmpty(), key = { it.key }) { item ->
                    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 3.dp)) {
                        MicroMetricRow(
                            label = item.label,
                            value = item.value,
                            target = item.target,
                            unit = item.unit,
                            pct = item.pct,
                        )
                    }
                }

                item {
                    Spacer(modifier = Modifier.height(8.dp))
                    Text("Comidas", style = MaterialTheme.typography.titleMedium)
                }

                items(state.meals, key = { "meal-${it.id}" }) { meal ->
                    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .padding(start = 12.dp, end = 4.dp, top = 8.dp, bottom = 8.dp),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Column(modifier = Modifier.weight(1f).padding(end = 8.dp)) {
                                Text(
                                    "${EsLabels.mealTypeEmoji(meal.meal_type)} ${EsLabels.mealType(meal.meal_type)}",
                                    style = MaterialTheme.typography.labelMedium,
                                    color = MaterialTheme.colorScheme.primary,
                                )
                                Text(meal.title ?: "Comida", style = MaterialTheme.typography.titleSmall)
                                Text(
                                    "${meal.calories.toInt()} kcal · P ${meal.protein_g.toInt()} " +
                                        "C ${meal.carbs_g.toInt()} G ${meal.fat_g.toInt()}",
                                )
                                Text(
                                    EsLabels.mealSource(meal.source),
                                    style = MaterialTheme.typography.labelSmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                )
                            }
                            IconButton(onClick = { onDeleteMeal(meal.id) }) {
                                Icon(
                                    Icons.Default.Delete,
                                    contentDescription = "Eliminar comida",
                                    tint = MaterialTheme.colorScheme.error,
                                )
                            }
                        }
                    }
                }

                item {
                    Spacer(modifier = Modifier.height(8.dp))
                    Button(onClick = onRefresh, modifier = Modifier.fillMaxWidth()) {
                        Text("Actualizar")
                    }
                    Spacer(modifier = Modifier.height(16.dp))
                }
            }
        }

        Text(
            disclaimer,
            style = MaterialTheme.typography.labelSmall,
            color = MaterialTheme.colorScheme.onSurfaceVariant.copy(alpha = 0.72f),
            modifier = Modifier
                .fillMaxWidth()
                .padding(top = 4.dp, bottom = 4.dp),
        )
    }

    if (showDatePicker) {
        val initialMillis = remember(selectedDate) {
            runCatching {
                LocalDate.parse(selectedDate.take(10))
                    .atStartOfDay(ZoneOffset.UTC)
                    .toInstant()
                    .toEpochMilli()
            }.getOrNull()
        }
        val pickerState = rememberDatePickerState(
            initialSelectedDateMillis = initialMillis,
            initialDisplayedMonthMillis = initialMillis,
        )
        DatePickerDialog(
            onDismissRequest = { showDatePicker = false },
            confirmButton = {
                TextButton(
                    onClick = {
                        pickerState.selectedDateMillis?.let { millis ->
                            val iso = Instant.ofEpochMilli(millis)
                                .atZone(ZoneOffset.UTC)
                                .toLocalDate()
                                .toString()
                            onPickDate(iso)
                        }
                        showDatePicker = false
                    },
                ) { Text("Elegir") }
            },
            dismissButton = {
                TextButton(onClick = { showDatePicker = false }) { Text("Cancelar") }
            },
        ) {
            DatePicker(state = pickerState)
        }
    }

    if (showMicroInfo) {
        AlertDialog(
            onDismissRequest = { showMicroInfo = false },
            confirmButton = {
                TextButton(onClick = { showMicroInfo = false }) { Text("Entendido") }
            },
            title = { Text("Micronutrientes") },
            text = {
                Text(
                    micronutrients?.info
                        ?: "Muchas vitaminas y minerales se almacenan en tu organismo. Lo relevante para tu salud no es cumplir el 100% cada día de forma estricta, sino mantener un promedio semanal equilibrado.",
                )
            },
        )
    }
}

private fun defaultMicroGroups(): Map<String, String> = mapOf(
    "all" to "Todos",
    "b_vitamins" to "Vitaminas B",
    "other_vitamins" to "Otras Vitaminas",
    "minerals" to "Minerales",
)

/** Convierte YYYY-MM-DD (o ISO) a dd/MM/yyyy. */
fun formatEsDate(iso: String): String {
    val day = iso.take(10)
    val parts = day.split("-")
    return if (parts.size == 3) "${parts[2]}/${parts[1]}/${parts[0]}" else iso
}

@Composable
private fun MetricCard(label: String, current: Double, target: Double?) {
    val t = (target ?: 1.0).coerceAtLeast(1.0)
    val progress = min(1f, (current / t).toFloat())
    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
        Column(modifier = Modifier.padding(12.dp)) {
            Text(label)
            Text("${current.toInt()} / ${target?.toInt() ?: "-"}")
            LinearProgressIndicator(progress = { progress }, modifier = Modifier.fillMaxWidth())
        }
    }
}

@Composable
private fun MicroMetricRow(label: String, value: Double, target: Double, unit: String, pct: Double) {
    val progress = min(1f, (pct / 100.0).toFloat())
    val valueText = if (value < 10) String.format("%.2f", value) else String.format("%.1f", value)
    Column(modifier = Modifier.fillMaxWidth().padding(12.dp)) {
        Text(label, style = MaterialTheme.typography.labelLarge)
        Text("$valueText $unit de $target $unit · ${pct.toInt()}%")
        LinearProgressIndicator(progress = { progress }, modifier = Modifier.fillMaxWidth())
    }
}
