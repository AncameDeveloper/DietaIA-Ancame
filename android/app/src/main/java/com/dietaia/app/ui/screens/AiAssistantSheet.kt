package com.dietaia.app.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun AiAssistantSheet(
    visible: Boolean,
    loading: Boolean,
    busyLabel: String? = null,
    message: String?,
    error: String?,
    onDismiss: () -> Unit,
    onRegisterMeal: (description: String) -> Unit,
    onOpenMeals: () -> Unit,
    onOpenTips: () -> Unit,
    onSuggestDiet: () -> Unit,
) {
    if (!visible) return

    val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
    var description by remember { mutableStateOf("") }

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
            Text("Asistente IA", style = MaterialTheme.typography.headlineSmall)
            Text(
                "Registra una comida con IA o accede a tips y sugerencias.",
                style = MaterialTheme.typography.bodyMedium,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )

            AiBusyBanner(loading = loading, label = busyLabel)

            OutlinedTextField(
                value = description,
                onValueChange = { description = it },
                label = { Text("¿Qué has comido?") },
                modifier = Modifier.fillMaxWidth(),
                minLines = 2,
                enabled = !loading,
            )

            Button(
                onClick = { onRegisterMeal(description.trim()) },
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

            OutlinedButton(
                onClick = onOpenTips,
                enabled = !loading,
                modifier = Modifier.fillMaxWidth(),
            ) {
                Text("Consejos IA")
            }

            OutlinedButton(
                onClick = onSuggestDiet,
                enabled = !loading,
                modifier = Modifier.fillMaxWidth(),
            ) {
                Text("Sugerir plan de dieta")
            }

            if (message != null) {
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
