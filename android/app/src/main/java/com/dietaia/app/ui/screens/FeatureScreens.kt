package com.dietaia.app.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.lazy.itemsIndexed
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.selection.toggleable
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.DropdownMenuItem
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.ExposedDropdownMenu
import androidx.compose.material3.ExposedDropdownMenuBox
import androidx.compose.material3.ExposedDropdownMenuDefaults
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Tab
import androidx.compose.material3.TabRow
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.material3.rememberModalBottomSheetState
import androidx.compose.runtime.Composable
import androidx.compose.runtime.CompositionLocalProvider
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateMapOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.material3.LocalMinimumInteractiveComponentSize
import com.dietaia.app.data.DietPlanDto
import com.dietaia.app.data.ShoppingListItemDto
import com.dietaia.app.data.TipDto
import com.dietaia.app.data.WeeklyMenuDto
@Composable
fun AiBusyBanner(loading: Boolean, label: String?) {
    if (!loading) return
    Card(
        modifier = Modifier
            .fillMaxWidth()
            .padding(bottom = 12.dp),
    ) {
        Row(
            modifier = Modifier
                .fillMaxWidth()
                .padding(14.dp),
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(12.dp),
        ) {
            CircularProgressIndicator(modifier = Modifier.size(22.dp), strokeWidth = 2.dp)
            Column(modifier = Modifier.weight(1f)) {
                Text("Trabajando…", style = MaterialTheme.typography.titleSmall)
                Text(
                    label ?: "Procesando…",
                    style = MaterialTheme.typography.bodySmall,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Spacer(modifier = Modifier.height(8.dp))
                LinearProgressIndicator(modifier = Modifier.fillMaxWidth())
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MealScreen(
    loading: Boolean,
    busyLabel: String?,
    error: String?,
    message: String?,
    onSubmitText: (String, String) -> Unit,
) {
    var description by remember { mutableStateOf("") }
    var mealType by remember { mutableStateOf("lunch") }
    var expanded by remember { mutableStateOf(false) }
    val types = listOf("breakfast", "lunch", "dinner", "snack")

    Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
        Text("Registrar comida", style = MaterialTheme.typography.headlineMedium)
        Text("La IA estimará calorías y nutrientes.")
        Spacer(Modifier = Modifier.height(12.dp))
        AiBusyBanner(loading = loading, label = busyLabel)
        ExposedDropdownMenuBox(expanded = expanded, onExpandedChange = { expanded = !expanded }) {
            OutlinedTextField(
                value = mealType,
                onValueChange = {},
                readOnly = true,
                label = { Text("Tipo") },
                trailingIcon = { ExposedDropdownMenuDefaults.TrailingIcon(expanded) },
                modifier = Modifier.menuAnchor().fillMaxWidth(),
            )
            ExposedDropdownMenu(expanded = expanded, onDismissRequest = { expanded = false }) {
                types.forEach {
                    DropdownMenuItem(
                        text = { Text(it) },
                        onClick = {
                            mealType = it
                            expanded = false
                        },
                    )
                }
            }
        }
        OutlinedTextField(
            value = description,
            onValueChange = { description = it },
            label = { Text("Descripción") },
            modifier = Modifier.fillMaxWidth().height(140.dp),
            enabled = !loading,
        )
        if (error != null) Text(error, color = MaterialTheme.colorScheme.error)
        if (message != null) Text(message, color = MaterialTheme.colorScheme.primary)
        Spacer(modifier = Modifier.height(12.dp))
        Button(
            onClick = { onSubmitText(description, mealType) },
            enabled = !loading && description.isNotBlank(),
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text(if (loading) "Analizando…" else "Guardar con IA")
        }
    }
}

@Composable
fun DietsScreen(
    plans: List<DietPlanDto>,
    loading: Boolean,
    busyLabel: String?,
    message: String?,
    onLoad: () -> Unit,
    onSelect: (Int) -> Unit,
    onSuggest: () -> Unit,
) {
    Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
        Text("Planes de dieta", style = MaterialTheme.typography.headlineMedium)
        AiBusyBanner(loading = loading, label = busyLabel)
        Button(onClick = onSuggest, enabled = !loading) {
            Text(if (loading) "Pensando…" else "Sugerir con IA")
        }
        if (message != null) Text(message)
        LazyColumn {
            items(plans) { plan ->
                Card(modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        Text(plan.name, style = MaterialTheme.typography.titleMedium)
                        Text(plan.description)
                        Button(onClick = { onSelect(plan.id) }, enabled = !loading) { Text("Seleccionar") }
                    }
                }
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun MenusScreen(
    dailyMenu: WeeklyMenuDto?,
    weeklyMenu: WeeklyMenuDto?,
    loading: Boolean,
    busyLabel: String?,
    shoppingItems: List<ShoppingListItemDto>,
    shoppingError: String?,
    onGenerate: (String) -> Unit,
    onLoad: (String) -> Unit,
    onOpenShoppingList: (String, WeeklyMenuDto?) -> Unit,
    onCloseShoppingList: () -> Unit,
) {
    var selectedTab by remember { mutableStateOf(0) } // 0 daily, 1 weekly
    var showShoppingSheet by remember { mutableStateOf(false) }
    val horizon = if (selectedTab == 0) "daily" else "weekly"
    val currentMenu = if (selectedTab == 0) dailyMenu else weeklyMenu
    val checked = remember { mutableStateMapOf<Int, Boolean>() }

    Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
        Text("Menús", style = MaterialTheme.typography.headlineMedium)
        AiBusyBanner(loading = loading, label = busyLabel)

        TabRow(selectedTabIndex = selectedTab) {
            Tab(
                selected = selectedTab == 0,
                onClick = {
                    selectedTab = 0
                    onLoad("daily")
                },
                text = { Text("Diaria") },
            )
            Tab(
                selected = selectedTab == 1,
                onClick = {
                    selectedTab = 1
                    onLoad("weekly")
                },
                text = { Text("Semanal") },
            )
        }

        Spacer(modifier = Modifier.height(12.dp))

        Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
            Button(
                onClick = { onGenerate(horizon) },
                enabled = !loading,
                modifier = Modifier.weight(1f),
            ) {
                Text(if (loading) "Generando…" else if (selectedTab == 0) "Generar diario" else "Generar semanal")
            }
            OutlinedButton(
                onClick = {
                    checked.clear()
                    showShoppingSheet = true
                    onOpenShoppingList(horizon, currentMenu)
                },
                enabled = !loading && currentMenu != null,
                modifier = Modifier.weight(1f),
            ) {
                Text("Lista de la compra")
            }
        }

        Spacer(modifier = Modifier.height(12.dp))

        Column(
            modifier = Modifier
                .weight(1f)
                .verticalScroll(rememberScrollState()),
        ) {
            currentMenu?.let { menu ->
                Text(
                    "${if (menu.horizon == "weekly") "Semanal" else "Diario"} · ${menu.notes ?: ""}",
                    style = MaterialTheme.typography.titleMedium,
                )
                Spacer(modifier = Modifier.height(8.dp))
                menu.content?.days.orEmpty().forEach { day ->
                    Text(day.date_label ?: "Día ${day.day}", style = MaterialTheme.typography.titleSmall)
                    day.meals.orEmpty().forEach { m ->
                        Text("${m.meal_type}: ${m.title} (${m.calories?.toInt() ?: 0} kcal)")
                    }
                    Spacer(modifier = Modifier.height(8.dp))
                }
            } ?: Text(
                if (selectedTab == 0) "Sin menú diario todavía. Pulsa generar."
                else "Sin menú semanal todavía. Pulsa generar.",
            )
        }
    }

    if (showShoppingSheet) {
        val sheetState = rememberModalBottomSheetState(skipPartiallyExpanded = true)
        ModalBottomSheet(
            onDismissRequest = {
                showShoppingSheet = false
                onCloseShoppingList()
            },
            sheetState = sheetState,
        ) {
            Column(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp)
                    .padding(bottom = 28.dp),
            ) {
                Text("Lista de la compra", style = MaterialTheme.typography.headlineSmall)
                Text(
                    if (selectedTab == 0) "Basada en el menú diario" else "Basada en el menú semanal",
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
                Spacer(modifier = Modifier.height(8.dp))
                if (loading) {
                    AiBusyBanner(loading = true, label = busyLabel ?: "Preparando lista…")
                }
                if (shoppingError != null) {
                    Text(shoppingError, color = MaterialTheme.colorScheme.error)
                }
                LazyColumn(modifier = Modifier.height(420.dp)) {
                    itemsIndexed(shoppingItems) { index, item ->
                        val isChecked = checked[index] == true
                        Row(
                            modifier = Modifier
                                .fillMaxWidth()
                                .toggleable(
                                    value = isChecked,
                                    onValueChange = { checked[index] = it },
                                    role = Role.Checkbox,
                                )
                                .padding(vertical = 8.dp),
                            verticalAlignment = Alignment.CenterVertically,
                        ) {
                            CompositionLocalProvider(LocalMinimumInteractiveComponentSize provides Dp.Unspecified) {
                                Checkbox(
                                    checked = isChecked,
                                    onCheckedChange = null,
                                    modifier = Modifier.padding(end = 12.dp),
                                )
                            }
                            Column(modifier = Modifier.weight(1f)) {
                                Text(
                                    item.name,
                                    style = MaterialTheme.typography.titleSmall.copy(
                                        textDecoration = if (isChecked) TextDecoration.LineThrough else TextDecoration.None,
                                    ),
                                    color = if (isChecked) {
                                        MaterialTheme.colorScheme.onSurfaceVariant
                                    } else {
                                        MaterialTheme.colorScheme.onSurface
                                    },
                                )
                                Text(
                                    item.quantity_label ?: "Al gusto / según receta",
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                                )
                            }
                        }
                    }
                }
                TextButton(
                    onClick = {
                        showShoppingSheet = false
                        onCloseShoppingList()
                    },
                    modifier = Modifier.align(Alignment.End),
                ) {
                    Text("Cerrar")
                }
            }
        }
    }
}

@Composable
fun TipsScreen(
    tips: List<TipDto>,
    loading: Boolean,
    busyLabel: String?,
    onLoad: () -> Unit,
    onRefresh: () -> Unit,
) {
    Column(modifier = Modifier.fillMaxSize().padding(16.dp)) {
        Text("Consejos", style = MaterialTheme.typography.headlineMedium)
        AiBusyBanner(loading = loading, label = busyLabel)
        Button(onClick = onRefresh, enabled = !loading) {
            Text(
                when {
                    loading -> "Generando…"
                    tips.isEmpty() -> "Generar consejos"
                    else -> "Actualizar con IA"
                },
            )
        }
        if (tips.isEmpty() && !loading) {
            Text("Pulsa generar para obtener recomendaciones con IA.")
        }
        LazyColumn {
            items(tips) { tip ->
                Card(modifier = Modifier.fillMaxWidth().padding(vertical = 6.dp)) {
                    Column(modifier = Modifier.padding(12.dp)) {
                        Text(tip.title ?: "Consejo", style = MaterialTheme.typography.titleMedium)
                        Text(tip.body ?: "")
                    }
                }
            }
        }
    }
}
