package com.dietaia.app.ui.screens

import androidx.compose.foundation.Canvas
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
import androidx.compose.material3.Card
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.FilterChip
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.geometry.Offset
import androidx.compose.ui.graphics.Path
import androidx.compose.ui.graphics.StrokeCap
import androidx.compose.ui.graphics.drawscope.Stroke
import androidx.compose.ui.unit.dp
import com.dietaia.app.data.WeightProgressResponse

@Composable
fun ProgressScreen(
    progress: WeightProgressResponse?,
    loading: Boolean,
    softNotice: String?,
    onReload: (Int) -> Unit,
) {
    val days = progress?.days ?: 90
    Column(
        modifier = Modifier
            .fillMaxSize()
            .padding(16.dp),
    ) {
        Text("Evolución del peso", style = MaterialTheme.typography.headlineSmall)
        Text(
            "Historial de los últimos días",
            style = MaterialTheme.typography.bodyMedium,
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
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
                    "Aún no hay registros de peso. Actualiza tu peso en el perfil (web) para ver la gráfica.",
                    modifier = Modifier.padding(16.dp),
                    style = MaterialTheme.typography.bodyMedium,
                )
            }
            return
        }

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
        LazyColumn(verticalArrangement = Arrangement.spacedBy(6.dp)) {
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
}
