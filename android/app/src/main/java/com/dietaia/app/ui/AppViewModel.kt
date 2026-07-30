package com.dietaia.app.ui

import android.content.Context
import android.net.Uri
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.dietaia.app.data.MealSuggestionDto
import com.dietaia.app.data.MealSuggestionsRequest
import com.dietaia.app.data.NutritionistChatMessageDto
import com.dietaia.app.data.NutritionistChatRequest
import com.dietaia.app.data.NutritionistContextDto
import com.dietaia.app.data.WeightLogRequest
import com.dietaia.app.data.WeightProgressResponse
import com.dietaia.app.data.WeeklyMenuDto
import com.dietaia.app.data.UserDto
import com.dietaia.app.data.TipDto
import com.dietaia.app.data.ShoppingListRequest
import com.dietaia.app.data.ShoppingListItemDto
import com.dietaia.app.data.RegisterRequest
import com.dietaia.app.data.MicronutrientsResponse
import com.dietaia.app.data.MenuGenerateRequest
import com.dietaia.app.data.MealCreateRequest
import com.dietaia.app.data.LoginRequest
import com.dietaia.app.data.GoogleAuthRequest
import com.dietaia.app.data.DietSelectRequest
import com.dietaia.app.data.DietPlanDto
import com.dietaia.app.data.DashboardResponse
import com.dietaia.app.data.ApiClient
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch
import okhttp3.MediaType.Companion.toMediaTypeOrNull
import okhttp3.MultipartBody
import okhttp3.RequestBody.Companion.toRequestBody
import retrofit2.HttpException
import java.time.LocalDate
import java.time.format.DateTimeFormatter

class AppViewModel : ViewModel() {
    private val api get() = ApiClient.create()
    private var busyJob: Job? = null

    var token by mutableStateOf<String?>(null)
        private set
    var loading by mutableStateOf(false)
        private set
    var busyLabel by mutableStateOf<String?>(null)
        private set
    var error by mutableStateOf<String?>(null)
    /** Aviso no bloqueante (p. ej. micronutrientes no disponibles). */
    var softNotice by mutableStateOf<String?>(null)
        private set
    var message by mutableStateOf<String?>(null)
        private set

    /** Se pone a true cuando la API responde 401: la Nav debe ir a Login. */
    var requireLogin by mutableStateOf(false)
        private set

    var dashboard by mutableStateOf<DashboardResponse?>(null)
    var micronutrients by mutableStateOf<MicronutrientsResponse?>(null)
    var microRange by mutableStateOf("7days")
        private set
    var microGroup by mutableStateOf("all")
        private set
    /** Fecha del día mostrado en Hoy (YYYY-MM-DD). */
    var selectedDate by mutableStateOf(LocalDate.now().format(ISO_DATE))
        private set
    var currentUser by mutableStateOf<UserDto?>(null)
        private set
    var weightProgress by mutableStateOf<WeightProgressResponse?>(null)
        private set
    var dietPlans by mutableStateOf<List<DietPlanDto>>(emptyList())
        private set
    var menu by mutableStateOf<WeeklyMenuDto?>(null)
        private set
    var dailyMenu by mutableStateOf<WeeklyMenuDto?>(null)
        private set
    var weeklyMenu by mutableStateOf<WeeklyMenuDto?>(null)
        private set
    var shoppingItems by mutableStateOf<List<ShoppingListItemDto>>(emptyList())
        private set
    var shoppingError by mutableStateOf<String?>(null)
        private set
    var tips by mutableStateOf<List<TipDto>>(emptyList())
        private set
    var nutritionistContext by mutableStateOf<NutritionistContextDto?>(null)
        private set
    var nutritionistMessages by mutableStateOf<List<NutritionistChatMessageDto>>(emptyList())
        private set
    var mealSuggestions by mutableStateOf<List<MealSuggestionDto>>(emptyList())
        private set
    var mealSuggestionSummary by mutableStateOf<String?>(null)
        private set

    companion object {
        private val ISO_DATE: DateTimeFormatter = DateTimeFormatter.ISO_LOCAL_DATE
    }

    init {
        ApiClient.setUnauthorizedHandler {
            viewModelScope.launch {
                handleUnauthorized()
            }
        }
    }

    fun applyAuthToken(value: String) {
        token = value
        ApiClient.setToken(value)
        requireLogin = false
    }

    private fun rememberUser(user: UserDto?) {
        if (user != null) currentUser = user
    }

    fun clearFeedback() {
        error = null
        softNotice = null
        message = null
    }

    fun consumeMessage() {
        message = null
    }

    fun consumeRequireLogin() {
        requireLogin = false
    }

    private fun handleUnauthorized() {
        token = null
        ApiClient.setToken(null)
        dashboard = null
        micronutrients = null
        currentUser = null
        weightProgress = null
        dietPlans = emptyList()
        tips = emptyList()
        nutritionistContext = null
        nutritionistMessages = emptyList()
        mealSuggestions = emptyList()
        mealSuggestionSummary = null
        dailyMenu = null
        weeklyMenu = null
        menu = null
        error = null
        softNotice = null
        message = null
        requireLogin = true
        endBusy()
    }

    private fun beginBusy(messages: List<String>) {
        loading = true
        busyJob?.cancel()
        busyLabel = messages.firstOrNull()
        if (messages.size <= 1) return
        busyJob = viewModelScope.launch {
            var i = 0
            while (isActive) {
                delay(2400)
                i = (i + 1) % messages.size
                busyLabel = messages[i]
            }
        }
    }

    private fun endBusy() {
        busyJob?.cancel()
        busyJob = null
        busyLabel = null
        loading = false
    }

    private fun ensureAuthed(): Boolean {
        val t = token ?: ApiClient.currentToken()
        if (t.isNullOrBlank()) {
            handleUnauthorized()
            return false
        }
        if (token == null) {
            applyAuthToken(t)
        }
        return true
    }

    private fun reportError(e: Exception) {
        if (e is HttpException && e.code() == 401) {
            handleUnauthorized()
            return
        }
        softNotice = null
        error = ApiClient.humanizeError(e)
    }

    /** Fallos no críticos: no pintan el error rojo de pantalla completa. */
    private fun reportSoftFailure(e: Exception, fallback: String) {
        if (e is HttpException && e.code() == 401) {
            handleUnauthorized()
            return
        }
        softNotice = ApiClient.humanizeError(e).takeIf { it.isNotBlank() } ?: fallback
    }

    fun login(email: String, password: String, onSuccess: (String) -> Unit) {
        viewModelScope.launch {
            beginBusy(listOf("Iniciando sesión…"))
            error = null
            try {
                val res = api.login(LoginRequest(email, password))
                applyAuthToken(res.token)
                rememberUser(res.user)
                onSuccess(res.token)
            } catch (e: Exception) {
                error = ApiClient.humanizeError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun loginWithGoogle(idToken: String, onSuccess: (String) -> Unit) {
        viewModelScope.launch {
            beginBusy(listOf("Conectando con Google…"))
            error = null
            try {
                val res = api.loginWithGoogle(GoogleAuthRequest(idToken))
                applyAuthToken(res.token)
                rememberUser(res.user)
                onSuccess(res.token)
            } catch (e: Exception) {
                error = ApiClient.humanizeError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun register(name: String, email: String, password: String, onSuccess: (String) -> Unit) {
        viewModelScope.launch {
            beginBusy(listOf("Creando cuenta…"))
            error = null
            try {
                val res = api.register(RegisterRequest(name, email, password, password))
                applyAuthToken(res.token)
                rememberUser(res.user)
                onSuccess(res.token)
            } catch (e: Exception) {
                error = ApiClient.humanizeError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun logout(onDone: () -> Unit) {
        viewModelScope.launch {
            try {
                if (!ApiClient.currentToken().isNullOrBlank()) {
                    api.logout()
                }
            } catch (_: Exception) {
            }
            token = null
            ApiClient.setToken(null)
            dashboard = null
            micronutrients = null
            currentUser = null
            weightProgress = null
            onDone()
        }
    }

    fun shiftSelectedDate(days: Long) {
        val next = LocalDate.parse(selectedDate, ISO_DATE).plusDays(days)
        val capped = minOf(next, LocalDate.now())
        selectedDate = capped.format(ISO_DATE)
        loadDashboard()
    }

    fun updateSelectedDate(iso: String) {
        val day = iso.take(10)
        val parsed = runCatching { LocalDate.parse(day, ISO_DATE) }.getOrNull() ?: return
        val capped = minOf(parsed, LocalDate.now()).format(ISO_DATE)
        if (capped == selectedDate) return
        selectedDate = capped
        loadDashboard()
    }

    fun goToToday() {
        val today = LocalDate.now().format(ISO_DATE)
        if (selectedDate == today) {
            loadDashboard()
        } else {
            selectedDate = today
            loadDashboard()
        }
    }

    fun loadDashboard() {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(listOf("Cargando tu día…"))
            error = null
            softNotice = null
            try {
                dashboard = api.dashboard(selectedDate)
                dashboard?.date?.take(10)?.let { if (it.isNotBlank()) selectedDate = it }
            } catch (e: Exception) {
                reportError(e)
                endBusy()
                return@launch
            }
            try {
                micronutrients = api.micronutrients(microRange, microGroup, selectedDate)
            } catch (e: Exception) {
                micronutrients = null
                reportSoftFailure(e, "No se pudieron cargar los micronutrientes.")
            } finally {
                endBusy()
            }
        }
    }

    fun updateMicroRange(range: String) {
        microRange = if (range == "today") "today" else "7days"
        reloadMicronutrients()
    }

    fun updateMicroGroup(group: String) {
        microGroup = group
        reloadMicronutrients()
    }

    private fun reloadMicronutrients() {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            try {
                micronutrients = api.micronutrients(microRange, microGroup, selectedDate)
                softNotice = null
            } catch (e: Exception) {
                micronutrients = null
                reportSoftFailure(e, "No se pudieron cargar los micronutrientes.")
            }
        }
    }

    fun createMeal(description: String, mealType: String, onDone: () -> Unit) {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(
                listOf(
                    "Analizando la comida…",
                    "Detectando todas las comidas del mensaje…",
                    "Estimando calorías y nutrientes…",
                    "Casi listo…",
                ),
            )
            error = null
            message = null
            try {
                val res = api.createMeal(MealCreateRequest(description, mealType, true))
                val count = res.count.takeIf { it > 0 } ?: res.registeredMeals().size
                message = res.message ?: if (count <= 1) "Comida registrada" else "$count comidas registradas"
                onDone()
            } catch (e: Exception) {
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun analyzeMealPhoto(context: Context, uri: Uri, mealType: String, onDone: () -> Unit) {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(
                listOf(
                    "Subiendo la foto…",
                    "Analizando el plato con IA…",
                    "Estimando calorías y nutrientes…",
                    "Casi listo…",
                ),
            )
            error = null
            message = null
            try {
                val resolver = context.contentResolver
                val bytes = resolver.openInputStream(uri)?.use { it.readBytes() }
                    ?: throw IllegalStateException("No se pudo leer la imagen.")
                val mime = resolver.getType(uri) ?: "image/jpeg"
                val body = bytes.toRequestBody(mime.toMediaTypeOrNull())
                val part = MultipartBody.Part.createFormData("photo", "comida.jpg", body)
                val typeBody = mealType.toRequestBody("text/plain".toMediaTypeOrNull())
                val confirmBody = "1".toRequestBody("text/plain".toMediaTypeOrNull())
                api.analyzePhoto(part, typeBody, confirmBody)
                message = "Comida registrada desde la foto"
                onDone()
            } catch (e: Exception) {
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun deleteMeal(mealId: Int) {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(listOf("Eliminando comida…"))
            error = null
            message = null
            try {
                api.deleteMeal(mealId)
                message = "Comida eliminada"
                dashboard = api.dashboard(selectedDate)
                try {
                    micronutrients = api.micronutrients(microRange, microGroup, selectedDate)
                    softNotice = null
                } catch (e: Exception) {
                    micronutrients = null
                    reportSoftFailure(e, "No se pudieron actualizar los micronutrientes.")
                }
            } catch (e: Exception) {
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun loadDiets() {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(listOf("Cargando planes…"))
            try {
                dietPlans = api.dietPlans()
            } catch (e: Exception) {
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun loadProfile() {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            try {
                currentUser = runCatching { api.profile() }.getOrElse { api.me() }
            } catch (e: Exception) {
                reportSoftFailure(e, "No se pudo cargar el perfil.")
            }
        }
    }

    fun loadWeightProgress(days: Int = 90) {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(listOf("Cargando progreso…"))
            error = null
            try {
                weightProgress = api.weightProgress(days)
            } catch (e: Exception) {
                weightProgress = null
                reportSoftFailure(e, "No se pudo cargar el progreso.")
            } finally {
                endBusy()
            }
        }
    }

    fun saveWeight(weightKg: Double, dateIso: String, note: String? = null, onDone: (() -> Unit)? = null) {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(listOf("Guardando peso…"))
            error = null
            softNotice = null
            message = null
            try {
                val capped = minOf(LocalDate.parse(dateIso.take(10), ISO_DATE), LocalDate.now())
                    .format(ISO_DATE)
                api.storeWeight(
                    WeightLogRequest(
                        weight = weightKg,
                        date = capped,
                        note = note?.takeIf { it.isNotBlank() },
                    ),
                )
                val days = weightProgress?.days ?: 90
                weightProgress = api.weightProgress(days)
                message = "Peso registrado (${capped.take(10)})"
                onDone?.invoke()
            } catch (e: Exception) {
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun loadNutritionistContext() {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            try {
                val res = api.nutritionistContext()
                nutritionistContext = res.context
            } catch (e: Exception) {
                reportSoftFailure(e, "No se pudo cargar el contexto del nutricionista.")
            }
        }
    }

    fun askNutritionist(messageText: String) {
        val trimmed = messageText.trim()
        if (trimmed.length < 3) {
            error = "Escribe una pregunta un poco más concreta."
            return
        }
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(
                listOf(
                    "Revisando tu perfil y dieta…",
                    "Analizando comidas recientes…",
                    "Preparando la respuesta…",
                    "Casi listo…",
                ),
            )
            error = null
            softNotice = null
            val prior = nutritionistMessages
            nutritionistMessages = prior + NutritionistChatMessageDto(role = "user", content = trimmed)
            try {
                val res = api.nutritionistChat(
                    NutritionistChatRequest(
                        message = trimmed,
                        history = prior.takeLast(8),
                    ),
                )
                nutritionistContext = res.context ?: nutritionistContext
                nutritionistMessages = nutritionistMessages + NutritionistChatMessageDto(
                    role = "assistant",
                    content = res.reply,
                )
            } catch (e: Exception) {
                nutritionistMessages = prior
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun requestMealSuggestions(prompt: String) {
        val trimmed = prompt.trim()
        if (trimmed.length < 3) return
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(
                listOf(
                    "Mirando tu historial…",
                    "Buscando huecos de nutrientes…",
                    "Preparando sugerencias…",
                    "Casi listo…",
                ),
            )
            error = null
            val prior = nutritionistMessages
            nutritionistMessages = prior + NutritionistChatMessageDto(role = "user", content = trimmed)
            try {
                val res = api.mealSuggestions(
                    MealSuggestionsRequest(
                        request = trimmed,
                        history = prior.takeLast(8),
                    ),
                )
                mealSuggestions = res.suggestions
                mealSuggestionSummary = res.summary
                nutritionistContext = res.context ?: nutritionistContext
                nutritionistMessages = nutritionistMessages + NutritionistChatMessageDto(
                    role = "assistant",
                    content = res.summary?.takeIf { it.isNotBlank() }
                        ?: "Aquí tienes opciones adaptadas a tu perfil y comidas recientes.",
                )
            } catch (e: Exception) {
                nutritionistMessages = prior
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun clearNutritionistSession() {
        nutritionistMessages = emptyList()
        mealSuggestions = emptyList()
        mealSuggestionSummary = null
        error = null
        softNotice = null
    }

    fun selectDiet(id: Int) {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(listOf("Activando plan…"))
            try {
                api.selectDiet(DietSelectRequest(id))
                message = "Plan seleccionado"
            } catch (e: Exception) {
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun suggestDiet() {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(
                listOf(
                    "Revisando tu perfil…",
                    "Comparando planes…",
                    "Eligiendo la mejor opción…",
                    "Casi listo…",
                ),
            )
            try {
                api.suggestDiet()
                message = "Plan sugerido por IA"
                dietPlans = api.dietPlans()
            } catch (e: Exception) {
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun generateMenu(horizon: String) {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(
                listOf(
                    "Analizando tu plan y objetivos…",
                    if (horizon == "weekly") "Creando el menú semanal…" else "Creando el menú diario…",
                    "Equilibrando calorías y macros…",
                    "Casi listo…",
                ),
            )
            try {
                val created = api.generateMenu(MenuGenerateRequest(horizon))
                menu = created
                if (horizon == "weekly") {
                    weeklyMenu = created
                } else {
                    dailyMenu = created
                }
            } catch (e: Exception) {
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }

    fun loadLatestMenu(horizon: String? = null) {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            try {
                if (horizon == null || horizon == "daily") {
                    dailyMenu = api.latestMenu("daily")
                }
                if (horizon == null || horizon == "weekly") {
                    weeklyMenu = api.latestMenu("weekly")
                }
                menu = when (horizon) {
                    "weekly" -> weeklyMenu ?: dailyMenu
                    "daily" -> dailyMenu ?: weeklyMenu
                    else -> dailyMenu ?: weeklyMenu ?: api.latestMenu()
                }
            } catch (e: Exception) {
                if (e is HttpException && e.code() == 401) {
                    handleUnauthorized()
                }
            }
        }
    }

    fun loadShoppingList(horizon: String, menu: WeeklyMenuDto?) {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            beginBusy(listOf("Preparando la lista de la compra…", "Consolidando ingredientes…"))
            shoppingError = null
            shoppingItems = emptyList()
            try {
                val body = when {
                    menu?.content != null -> ShoppingListRequest(
                        menu_id = menu.id,
                        horizon = menu.horizon.ifBlank { horizon },
                        content = menu.content,
                    )
                    else -> ShoppingListRequest(horizon = horizon)
                }
                val res = api.shoppingList(body)
                shoppingItems = res.items
                if (res.items.isEmpty()) {
                    shoppingError = "No se pudieron extraer ingredientes de este menú."
                }
            } catch (e: Exception) {
                if (e is HttpException && e.code() == 401) {
                    handleUnauthorized()
                } else {
                    shoppingError = ApiClient.humanizeError(e)
                }
            } finally {
                endBusy()
            }
        }
    }

    fun clearShoppingList() {
        shoppingItems = emptyList()
        shoppingError = null
    }

    fun loadTips(forceRefresh: Boolean = false) {
        viewModelScope.launch {
            if (!ensureAuthed()) return@launch
            if (forceRefresh) {
                beginBusy(
                    listOf(
                        "Analizando tu plan e historial…",
                        "Generando consejos personalizados…",
                        "Afinando recomendaciones…",
                        "Casi listo…",
                    ),
                )
            } else {
                loading = true
                busyLabel = null
            }
            try {
                tips = api.tips(refresh = forceRefresh).tips
            } catch (e: Exception) {
                reportError(e)
            } finally {
                endBusy()
            }
        }
    }
}
