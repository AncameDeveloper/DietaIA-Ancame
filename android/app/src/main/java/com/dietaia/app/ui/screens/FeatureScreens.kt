package com.dietaia.app.ui.screens

import android.net.Uri
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ExperimentalLayoutApi
import androidx.compose.foundation.layout.FlowRow
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
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.CameraAlt
import androidx.compose.material.icons.filled.Lightbulb
import androidx.compose.material.icons.filled.PhotoLibrary
import androidx.compose.material.icons.filled.RestaurantMenu
import androidx.compose.material.icons.filled.ShoppingCart
import androidx.compose.material3.AssistChip
import androidx.compose.material3.Button
import androidx.compose.material3.Card
import androidx.compose.material3.CardDefaults
import androidx.compose.material3.Checkbox
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FilterChip
import androidx.compose.material3.Icon
import androidx.compose.material3.LinearProgressIndicator
import androidx.compose.material3.LocalMinimumInteractiveComponentSize
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.ModalBottomSheet
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.SuggestionChip
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
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.semantics.Role
import androidx.compose.ui.text.style.TextDecoration
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.core.content.ContextCompat
import androidx.core.content.FileProvider
import android.Manifest
import android.content.pm.PackageManager
import com.dietaia.app.data.DietPlanDto
import com.dietaia.app.data.MenuMealDto
import com.dietaia.app.data.ShoppingListItemDto
import com.dietaia.app.data.TipDto
import com.dietaia.app.data.WeeklyMenuDto
import com.dietaia.app.ui.EsLabels
import java.io.File

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

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)
@Composable
fun MealScreen(
    loading: Boolean,
    busyLabel: String?,
    error: String?,
    message: String?,
    onSubmitText: (String, String) -> Unit,
    onSubmitPhoto: (Uri, String) -> Unit,
) {
    val context = LocalContext.current
    var description by remember { mutableStateOf("") }
    var mealType by remember { mutableStateOf("lunch") }
    var pendingCameraUri by remember { mutableStateOf<Uri?>(null) }
    var photoHint by remember { mutableStateOf<String?>(null) }

    fun createCameraUri(): Uri {
        val dir = File(context.cacheDir, "meal_photos").apply { mkdirs() }
        val file = File(dir, "capture_${System.currentTimeMillis()}.jpg")
        return FileProvider.getUriForFile(context, "${context.packageName}.fileprovider", file)
    }

    val takePicture = rememberLauncherForActivityResult(ActivityResultContracts.TakePicture()) { ok ->
        val uri = pendingCameraUri
        if (ok && uri != null) {
            photoHint = "Foto lista para analizar"
            onSubmitPhoto(uri, mealType)
        }
    }

    val pickGallery = rememberLauncherForActivityResult(ActivityResultContracts.GetContent()) { uri ->
        if (uri != null) {
            photoHint = "Imagen seleccionada"
            onSubmitPhoto(uri, mealType)
        }
    }

    val requestCameraPermission = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestPermission(),
    ) { granted ->
        if (granted) {
            val uri = createCameraUri()
            pendingCameraUri = uri
            takePicture.launch(uri)
        } else {
            photoHint = "Se necesita permiso de cámara"
        }
    }

    fun launchCamera() {
        val granted = ContextCompat.checkSelfPermission(context, Manifest.permission.CAMERA) ==
            PackageManager.PERMISSION_GRANTED
        if (granted) {
            val uri = createCameraUri()
            pendingCameraUri = uri
            takePicture.launch(uri)
        } else {
            requestCameraPermission.launch(Manifest.permission.CAMERA)
        }
    }

    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(16.dp),
    ) {
        Text("Registrar comida", style = MaterialTheme.typography.headlineMedium)
        Text(
            "Describe el plato o sube una foto. La IA estimará calorías y nutrientes.",
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Spacer(modifier = Modifier.height(12.dp))
        AiBusyBanner(loading = loading, label = busyLabel)

        Text("Tipo de comida", style = MaterialTheme.typography.labelLarge)
        Spacer(modifier = Modifier.height(6.dp))
        FlowRow(
            horizontalArrangement = Arrangement.spacedBy(8.dp),
            verticalArrangement = Arrangement.spacedBy(4.dp),
        ) {
            EsLabels.mealTypeOptions.forEach { (value, label) ->
                FilterChip(
                    selected = mealType == value,
                    onClick = { mealType = value },
                    enabled = !loading,
                    label = { Text("${EsLabels.mealTypeEmoji(value)} $label") },
                )
            }
        }

        Spacer(modifier = Modifier.height(10.dp))
        OutlinedTextField(
            value = description,
            onValueChange = { description = it },
            label = { Text("Descripción") },
            placeholder = { Text("Ej: pechuga a la plancha con ensalada") },
            modifier = Modifier.fillMaxWidth().height(140.dp),
            enabled = !loading,
        )

        Spacer(modifier = Modifier.height(12.dp))
        Text("O registra con foto", style = MaterialTheme.typography.titleSmall)
        Spacer(modifier = Modifier.height(8.dp))
        Row(horizontalArrangement = Arrangement.spacedBy(8.dp), modifier = Modifier.fillMaxWidth()) {
            OutlinedButton(
                onClick = { launchCamera() },
                enabled = !loading,
                modifier = Modifier.weight(1f),
            ) {
                Icon(Icons.Default.CameraAlt, contentDescription = null, modifier = Modifier.size(18.dp))
                Spacer(modifier = Modifier.size(6.dp))
                Text("Cámara")
            }
            OutlinedButton(
                onClick = { pickGallery.launch("image/*") },
                enabled = !loading,
                modifier = Modifier.weight(1f),
            ) {
                Icon(Icons.Default.PhotoLibrary, contentDescription = null, modifier = Modifier.size(18.dp))
                Spacer(modifier = Modifier.size(6.dp))
                Text("Galería")
            }
        }
        if (photoHint != null) {
            Text(photoHint!!, style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.primary)
        }

        if (error != null) {
            Spacer(modifier = Modifier.height(8.dp))
            Text(error, color = MaterialTheme.colorScheme.error)
        }
        if (message != null) {
            Spacer(modifier = Modifier.height(8.dp))
            Text(message, color = MaterialTheme.colorScheme.primary)
        }

        Spacer(modifier = Modifier.height(12.dp))
        Button(
            onClick = { onSubmitText(description, mealType) },
            enabled = !loading && description.isNotBlank(),
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text(if (loading) "Analizando…" else "Guardar con IA (texto)")
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

@OptIn(ExperimentalMaterial3Api::class, ExperimentalLayoutApi::class)
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
    var selectedTab by remember { mutableStateOf(0) }
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
                text = { Text("Diario") },
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
                Text(
                    if (loading) "Generando…"
                    else if (selectedTab == 0) "Generar diario"
                    else "Generar semanal",
                )
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
                Icon(Icons.Default.ShoppingCart, contentDescription = null, modifier = Modifier.size(18.dp))
                Spacer(modifier = Modifier.size(6.dp))
                Text("Lista compra")
            }
        }

        Spacer(modifier = Modifier.height(12.dp))

        LazyColumn(modifier = Modifier.weight(1f), verticalArrangement = Arrangement.spacedBy(10.dp)) {
            val menu = currentMenu
            if (menu == null) {
                item {
                    Card(modifier = Modifier.fillMaxWidth()) {
                        Text(
                            if (selectedTab == 0) {
                                "Sin menú diario todavía. Pulsa generar."
                            } else {
                                "Sin menú semanal todavía. Pulsa generar."
                            },
                            modifier = Modifier.padding(16.dp),
                            color = MaterialTheme.colorScheme.onSurfaceVariant,
                        )
                    }
                }
            } else {
                item {
                    Card(
                        modifier = Modifier.fillMaxWidth(),
                        colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.primaryContainer),
                    ) {
                        Column(modifier = Modifier.padding(14.dp)) {
                            Row(verticalAlignment = Alignment.CenterVertically) {
                                Icon(Icons.Default.RestaurantMenu, contentDescription = null)
                                Spacer(modifier = Modifier.size(8.dp))
                                Text(
                                    "Menú ${EsLabels.horizon(menu.horizon)}",
                                    style = MaterialTheme.typography.titleMedium,
                                )
                            }
                            if (!menu.notes.isNullOrBlank()) {
                                Spacer(modifier = Modifier.height(6.dp))
                                Text(menu.notes, style = MaterialTheme.typography.bodyMedium)
                            }
                            if (!menu.content?.notes.isNullOrBlank()) {
                                Spacer(modifier = Modifier.height(4.dp))
                                Text(
                                    menu.content?.notes.orEmpty(),
                                    style = MaterialTheme.typography.bodySmall,
                                    color = MaterialTheme.colorScheme.onPrimaryContainer,
                                )
                            }
                        }
                    }
                }

                menu.content?.days.orEmpty().forEach { day ->
                    item {
                        Text(
                            day.date_label ?: "Día ${day.day ?: ""}",
                            style = MaterialTheme.typography.titleSmall,
                            modifier = Modifier.padding(top = 4.dp, bottom = 2.dp),
                        )
                    }
                    items(day.meals.orEmpty()) { meal ->
                        MenuMealCard(meal)
                    }
                }
            }
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

@OptIn(ExperimentalLayoutApi::class)
@Composable
private fun MenuMealCard(meal: MenuMealDto) {
    val ingredients = (meal.ingredients ?: meal.items).orEmpty()
    Card(modifier = Modifier.fillMaxWidth()) {
        Column(modifier = Modifier.padding(14.dp)) {
            Row(
                modifier = Modifier.fillMaxWidth(),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.Top,
            ) {
                Column(modifier = Modifier.weight(1f)) {
                    Text(
                        "${EsLabels.mealTypeEmoji(meal.meal_type)} ${EsLabels.mealType(meal.meal_type)}",
                        style = MaterialTheme.typography.labelLarge,
                        color = MaterialTheme.colorScheme.primary,
                    )
                    Text(meal.title ?: "Comida", style = MaterialTheme.typography.titleMedium)
                }
                if (meal.calories != null) {
                    AssistChip(
                        onClick = {},
                        label = { Text("${meal.calories.toInt()} kcal") },
                        enabled = false,
                    )
                }
            }
            if (!meal.description.isNullOrBlank()) {
                Spacer(modifier = Modifier.height(6.dp))
                Text(
                    meal.description,
                    style = MaterialTheme.typography.bodyMedium,
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
            if (ingredients.isNotEmpty()) {
                Spacer(modifier = Modifier.height(8.dp))
                FlowRow(horizontalArrangement = Arrangement.spacedBy(6.dp), verticalArrangement = Arrangement.spacedBy(4.dp)) {
                    ingredients.take(8).forEach { ing ->
                        val label = buildString {
                            append(ing.name ?: "Ingrediente")
                            ing.quantity_g?.let { append(" · ${it.toInt()} g") }
                        }
                        SuggestionChip(onClick = {}, label = { Text(label) }, enabled = false)
                    }
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
        Text(
            "Recomendaciones personalizadas según tu plan e historial.",
            color = MaterialTheme.colorScheme.onSurfaceVariant,
        )
        Spacer(modifier = Modifier.height(8.dp))
        AiBusyBanner(loading = loading, label = busyLabel)
        Button(onClick = onRefresh, enabled = !loading, modifier = Modifier.fillMaxWidth()) {
            Text(
                when {
                    loading -> "Generando…"
                    tips.isEmpty() -> "Generar consejos"
                    else -> "Actualizar con IA"
                },
            )
        }
        Spacer(modifier = Modifier.height(8.dp))
        if (tips.isEmpty() && !loading) {
            Card(modifier = Modifier.fillMaxWidth()) {
                Text(
                    "Pulsa generar para obtener recomendaciones con IA.",
                    modifier = Modifier.padding(16.dp),
                    color = MaterialTheme.colorScheme.onSurfaceVariant,
                )
            }
        }
        LazyColumn(verticalArrangement = Arrangement.spacedBy(10.dp)) {
            items(tips) { tip ->
                Card(
                    modifier = Modifier.fillMaxWidth(),
                    colors = CardDefaults.cardColors(containerColor = MaterialTheme.colorScheme.surfaceVariant),
                ) {
                    Row(
                        modifier = Modifier.padding(14.dp),
                        horizontalArrangement = Arrangement.spacedBy(12.dp),
                    ) {
                        Icon(
                            Icons.Default.Lightbulb,
                            contentDescription = null,
                            tint = MaterialTheme.colorScheme.primary,
                        )
                        Column(modifier = Modifier.weight(1f)) {
                            Text(tip.title ?: "Consejo", style = MaterialTheme.typography.titleMedium)
                            Spacer(modifier = Modifier.height(4.dp))
                            Text(
                                tip.body ?: "",
                                style = MaterialTheme.typography.bodyMedium,
                                color = MaterialTheme.colorScheme.onSurfaceVariant,
                            )
                        }
                    }
                }
            }
        }
    }
}
