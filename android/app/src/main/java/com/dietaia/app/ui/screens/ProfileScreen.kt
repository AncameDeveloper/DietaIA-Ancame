package com.dietaia.app.ui.screens

import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.KeyboardArrowRight
import androidx.compose.material.icons.automirrored.filled.Logout
import androidx.compose.material.icons.automirrored.filled.ShowChart
import androidx.compose.material.icons.filled.Person
import androidx.compose.material.icons.filled.Restaurant
import androidx.compose.material3.Button
import androidx.compose.material3.ButtonDefaults
import androidx.compose.material3.Card
import androidx.compose.material3.HorizontalDivider
import androidx.compose.material3.Icon
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.unit.dp
import com.dietaia.app.data.DashboardResponse
import com.dietaia.app.data.UserDto

@Composable
fun ProfileScreen(
    user: UserDto?,
    dashboard: DashboardResponse?,
    softNotice: String?,
    onOpenDiets: () -> Unit,
    onOpenProgress: () -> Unit,
    onLogout: () -> Unit,
) {
    val name = user?.name ?: "Usuario"
    val email = user?.email.orEmpty()
    val profile = user?.profile ?: dashboard?.profile
    val dietName = dashboard?.diet?.name ?: "Sin plan activo"

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
        verticalArrangement = Arrangement.spacedBy(12.dp),
    ) {
        Card(modifier = Modifier.fillMaxWidth()) {
            Row(
                modifier = Modifier.fillMaxWidth().padding(16.dp),
                verticalAlignment = Alignment.CenterVertically,
                horizontalArrangement = Arrangement.spacedBy(12.dp),
            ) {
                Icon(Icons.Default.Person, contentDescription = null, modifier = Modifier.padding(4.dp))
                Column(modifier = Modifier.weight(1f)) {
                    Text(name, style = MaterialTheme.typography.titleLarge)
                    if (email.isNotBlank()) {
                        Text(
                            email,
                            style = MaterialTheme.typography.bodyMedium,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                    Text(
                        "Plan: $dietName",
                        style = MaterialTheme.typography.bodySmall,
                        color = MaterialTheme.colorScheme.onSurfaceVariant,
                    )
                }
            }
        }

        if (profile != null) {
            Card(modifier = Modifier.fillMaxWidth()) {
                Column(modifier = Modifier.padding(16.dp), verticalArrangement = Arrangement.spacedBy(6.dp)) {
                    Text("Tus datos", style = MaterialTheme.typography.titleMedium)
                    profile.weight_kg?.let { Text("Peso: ${it} kg") }
                    profile.height_cm?.let { Text("Altura: ${it.toInt()} cm") }
                    profile.age?.let { Text("Edad: $it") }
                    profile.calorie_target?.let { Text("Objetivo kcal: $it") }
                    if (listOfNotNull(
                            profile.weight_kg,
                            profile.height_cm,
                            profile.age,
                            profile.calorie_target,
                        ).isEmpty()
                    ) {
                        Text(
                            "Completa tu perfil en la web para ver más datos aquí.",
                            style = MaterialTheme.typography.bodySmall,
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
            }
        }

        if (softNotice != null) {
            Text(
                softNotice,
                style = MaterialTheme.typography.bodySmall,
                color = MaterialTheme.colorScheme.onSurfaceVariant,
            )
        }

        Text("Ajustes", style = MaterialTheme.typography.titleMedium)
        ProfileNavRow(
            icon = { Icon(Icons.Default.Restaurant, contentDescription = null) },
            title = "Configuración de dietas",
            subtitle = "Ayuno, Keto y otros planes",
            onClick = onOpenDiets,
        )
        ProfileNavRow(
            icon = { Icon(Icons.AutoMirrored.Filled.ShowChart, contentDescription = null) },
            title = "Progreso",
            subtitle = "Gráficas e historial de evolución",
            onClick = onOpenProgress,
        )

        Spacer(modifier = Modifier.height(8.dp))
        HorizontalDivider()
        Spacer(modifier = Modifier.height(8.dp))

        Button(
            onClick = onLogout,
            modifier = Modifier.fillMaxWidth(),
            colors = ButtonDefaults.buttonColors(
                containerColor = MaterialTheme.colorScheme.error,
                contentColor = MaterialTheme.colorScheme.onError,
            ),
        ) {
            Icon(Icons.AutoMirrored.Filled.Logout, contentDescription = null)
            Spacer(modifier = Modifier.padding(horizontal = 6.dp))
            Text("Salir / Cerrar sesión")
        }
    }
}

@Composable
private fun ProfileNavRow(
    icon: @Composable () -> Unit,
    title: String,
    subtitle: String,
    onClick: () -> Unit,
) {
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .clickable(onClick = onClick),
    ) {
        Row(
            modifier = Modifier.fillMaxWidth().padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            icon()
            Column(modifier = Modifier.weight(1f)) {
                Text(title, style = MaterialTheme.typography.titleSmall)
                Text(
                    subtitle,
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            Icon(Icons.AutoMirrored.Filled.KeyboardArrowRight, contentDescription = null)
        }
    }
}
