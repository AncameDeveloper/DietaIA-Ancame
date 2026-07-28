package com.dietaia.app.ui.screens

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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Delete
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.dietaia.app.data.DashboardResponse
import kotlin.math.min

@Composable
fun DashboardScreen(
    state: DashboardResponse?,
    loading: Boolean,
    error: String?,
    onRefresh: () -> Unit,
    onDeleteMeal: (Int) -> Unit,
    onLogout: () -> Unit,
) {
    Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
        Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
            Column {
                Text("Hoy", style = MaterialTheme.typography.headlineMedium)
                Text(state?.diet?.name ?: "Sin plan", style = MaterialTheme.typography.bodyMedium)
                state?.date?.let {
                    Text(formatEsDate(it), style = MaterialTheme.typography.labelLarge)
                }
            }
            TextButton(onClick = onLogout) { Text("Salir") }
        }
        if (loading && state == null) {
            CircularProgressIndicator()
            return
        }
        if (error != null) Text(error, color = MaterialTheme.colorScheme.error)
        state?.let { dash ->
            Text(dash.disclaimer ?: "", style = MaterialTheme.typography.labelSmall)
            Spacer(Modifier.height(12.dp))
            MetricCard("Calorías", dash.summary?.calories ?: 0.0, dash.targets?.calories)
            MetricCard("Proteína", dash.summary?.protein_g ?: 0.0, dash.targets?.protein_g)
            MetricCard("Carbos", dash.summary?.carbs_g ?: 0.0, dash.targets?.carbs_g)
            MetricCard("Grasas", dash.summary?.fat_g ?: 0.0, dash.targets?.fat_g)
            Spacer(Modifier.height(8.dp))
            Text("Comidas", style = MaterialTheme.typography.titleMedium)
            LazyColumn(modifier = Modifier.weight(1f, fill = false)) {
                items(dash.meals, key = { it.id }) { meal ->
                    Card(modifier = Modifier.fillMaxWidth().padding(vertical = 4.dp)) {
                        Row(
                            modifier = Modifier.fillMaxWidth().padding(start = 12.dp, end = 4.dp, top = 8.dp, bottom = 8.dp),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            Column(modifier = Modifier.weight(1f).padding(end = 8.dp)) {
                                Text(meal.title ?: "Comida", style = MaterialTheme.typography.titleSmall)
                                Text("${meal.calories.toInt()} kcal · P ${meal.protein_g.toInt()} C ${meal.carbs_g.toInt()} G ${meal.fat_g.toInt()}")
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
            }
        }
        Spacer(Modifier.height(8.dp))
        Button(onClick = onRefresh) { Text("Actualizar") }
    }
}

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
