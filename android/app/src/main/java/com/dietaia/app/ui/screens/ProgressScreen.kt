package com.dietaia.app.ui.screens

import androidx.compose.foundation.Canvas
import androidx.compose.foundation.clickable
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
import androidx.compose.foundation.text.KeyboardOptions
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DatePicker
import androidx.compose.material3.DatePickerDialog
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Snackbar
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberDatePickerState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.text.input.KeyboardType
import androidx.compose.ui.unit.dp
import com.dietaia.app.data.WeightProgressResponse
import java.time.Instant
import java.time.LocalDate
import java.time.ZoneOffset
import kotlinx.coroutines.delay

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun ProgressScreen(
    progress: WeightProgressResponse?,
    loading: Boolean,
    softNotice: String?,
    message: String?,
    error: String?,
    onReload: (Int) -> Unit,
    onSaveWeight: (weightKg: Double, dateIso: String, note: String?) -> Unit,
    onConsumeMessage: () -> Unit,
) {
    val days = progress?.days ?: 90
    var showForm by remember { mutableStateOf(false) }
    var showDatePicker by remember { mutableStateOf(false) }
    var weightText by remember { mutableStateOf("") }
    var noteText by remember { mutableStateOf("") }
    var selectedDate by remember { mutableStateOf(LocalDate.now().toString()) }
    var formError by remember { mutableStateOf<String?>(null) }
    var snackbarText by remember { mutableStateOf<String?>(null) }

    LaunchedEffect(message) {
        if (!message.isNullOrBlank()) {
            snackbarText = message
            showForm = false
            onConsumeMessage()
            delay(2800)
            if (snackbarText == message) snackbarText = null
        }
    }
    LaunchedEffect(error) {
        if (!error.isNullOrBlank() && showForm) {
            formError = error
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth(),
            horizontalArrangement = Arrangement.SpaceBetween,
            verticalAlignment = Alignment.CenterVertically,
        ) {
            Column(modifier = Modifier.weight(1f)) {
                Text("Evolución del peso", style = MaterialTheme.typography.headlineSmall)
                Text(
                    "Historial de los últimos días",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            Button(
                onClick = {
                    weightText = progress?.items?.lastOrNull()?.weight?.toString().orEmpty()
                    noteText = ""
                    selectedDate = LocalDate.now().toString()
                    formError = null
                    showForm = true
                },
            ) {
                Text("Registrar peso")
            }
        }

        Spacer(modifier = Modifier.height(12.dp))

        Row(horizontalArrangement = Arrangement.spacedBy(8.dp)) {
            listOf(30, 90, 180).forEach { d ->
                FilterChip(
                    selected = days == d,
                    onClick = { onReload(d) },
                    label = { Text("${d}d") },
                )
            }
        }

        Spacer(modifier = Modifier.height(12.dp))

        if (loading && progress == null) {
            CircularProgressIndicator(modifier = Modifier.align(Alignment.CenterHorizontally))
            return
        }
        if (softNotice != null) {
            Text(
                softNotice,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
            Spacer(modifier = Modifier.height(8.dp))
        }

        val items = progress?.items.orEmpty()
        if (items.isEmpty()) {
            Card(modifier = Modifier.fillMaxWidth()) {
                Text(
                    "Aún no hay registros de peso. Pulsa «Registrar peso» para empezar.",
                    modifier = Modifier.padding(16.dp),
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
        } else {
            val lineColor = MaterialTheme.colorScheme.primary
            val axisColor = MaterialTheme.colorScheme.outlineVariant
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(modifier = Modifier.padding(12.dp)) {
                    Text(
                        "${progress?.count ?: 0} puntos · ${progress?.min ?: 0}–${progress?.max ?: 0} kg",
                        style = MaterialTheme.typography.labelMedium,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                    Spacer(modifier = Modifier.height(8.dp))
                    Canvas(
                        modifier = Modifier
                            .fillMaxWidth()
                            .height(200.dp),
                    ) {
                        val minW = (progress?.min ?: items.minOf { it.weight }).toFloat()
                        val maxW = (progress?.max ?: items.maxOf { it.weight }).toFloat()
                        val span = (maxW - minW).coerceAtLeast(1f)
                        val left = 8f
                        val right = size.width - 8f
                        val top = 12f
                        val bottom = size.height - 12f
                        val usableW = right - left
                        val usableH = bottom - top

                        drawLine(axisColor, Offset(left, bottom), Offset(right, bottom), strokeWidth = 2f)
                        drawLine(axisColor, Offset(left, top), Offset(left, bottom), strokeWidth = 2f)

                        if (items.size == 1) {
                            val y = bottom - ((items[0].weight.toFloat() - minW) / span) * usableH
                            drawCircle(lineColor, radius = 6f, center = Offset(left + usableW / 2f, y))
                        } else {
                            val path = Path()
                            items.forEachIndexed { index, point ->
                                val x = left + (index.toFloat() / (items.size - 1)) * usableW
                                val y = bottom - ((point.weight.toFloat() - minW) / span) * usableH
                                if (index == 0) path.moveTo(x, y) else path.lineTo(x, y)
                            }
                            drawPath(
                                path,
                                color = lineColor,
                                style = Stroke(width = 4f, cap = StrokeCap.Round),
                            )
                            items.forEachIndexed { index, point ->
                                val x = left + (index.toFloat() / (items.size - 1)) * usableW
                                val y = bottom - ((point.weight.toFloat() - minW) / span) * usableH
                                drawCircle(lineColor, radius = 4f, center = Offset(x, y))
                            }
                        }
                    }
                }
            }

            Spacer(modifier = Modifier.height(12.dp))
            Text("Historial", style = MaterialTheme.typography.titleMedium)
            LazyColumn(
                modifier = Modifier.weight(1f),
                verticalArrangement = Arrangement.spacedBy(6.dp),
            ) {
                items(items.asReversed(), key = { it.date }) { point ->
                    Card(modifier = Modifier.fillMaxWidth()) {
                        Row(
                            modifier = Modifier.fillMaxWidth().padding(12.dp),
                            horizontalArrangement = Arrangement.SpaceBetween,
                        ) {
                            Text(formatEsDate(point.date))
                            Text("${point.weight} kg", style = MaterialTheme.typography.titleSmall)
                        }
                    }
                }
            }
        }

        snackbarText?.let { text ->
            Spacer(modifier = Modifier.height(8.dp))
            Snackbar(
                action = {
                    TextButton(onClick = { snackbarText = null }) { Text("OK") }
                },
            ) { Text(text) }
        }
    }

    if (showForm) {
        AlertDialog(
            onDismissRequest = { if (!loading) showForm = false },
            title = { Text("Registrar peso") },
            text = {
                Column(verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    OutlinedTextField(
                        value = weightText,
                        onValueChange = {
                            weightText = it.replace(',', '.')
                            formError = null
                        },
                        label = { Text("Peso (kg)") },
                        singleLine = true,
                        keyboardOptions = KeyboardOptions(keyboardType = KeyboardType.Decimal),
                        modifier = Modifier.fillMaxWidth(),
                    )
                    OutlinedTextField(
                        value = formatEsDate(selectedDate),
                        onValueChange = {},
                        readOnly = true,
                        label = { Text("Fecha") },
                        trailingIcon = {
                            IconButton(onClick = { showDatePicker = true }) {
                                Icon(Icons.Default.CalendarMonth, contentDescription = "Elegir fecha")
                            }
                        },
                        modifier = Modifier
                            .fillMaxWidth()
                            .clickable { showDatePicker = true },
                    )
                    OutlinedTextField(
                        value = noteText,
                        onValueChange = { noteText = it },
                        label = { Text("Nota (opcional)") },
                        singleLine = true,
                        modifier = Modifier.fillMaxWidth(),
                    )
                    formError?.let {
                        Text(it, color = MaterialTheme.colorScheme.error, style = MaterialTheme.typography.bodySmall)
                    }
                }
            },
            confirmButton = {
                TextButton(
                    enabled = !loading,
                    onClick = {
                        val value = weightText.toDoubleOrNull()
                        if (value == null || value < 30.0 || value > 300.0) {
                            formError = "Indica un peso entre 30 y 300 kg."
                            return@TextButton
                        }
                        onSaveWeight(value, selectedDate, noteText.ifBlank { null })
                    },
                ) { Text(if (loading) "Guardando…" else "Guardar") }
            },
            dismissButton = {
                TextButton(enabled = !loading, onClick = { showForm = false }) { Text("Cancelar") }
            },
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
                            val picked = Instant.ofEpochMilli(millis)
                                .atZone(ZoneOffset.UTC)
                                .toLocalDate()
                            selectedDate = minOf(picked, LocalDate.now()).toString()
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
}
