package com.dietaia.app.ui

import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.setValue
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.dietaia.app.data.ApiClient
import com.dietaia.app.data.DashboardResponse
import com.dietaia.app.data.DietPlanDto
import com.dietaia.app.data.DietSelectRequest
import com.dietaia.app.data.GoogleAuthRequest
import com.dietaia.app.data.LoginRequest
import com.dietaia.app.data.MealCreateRequest
import com.dietaia.app.data.MenuGenerateRequest
import com.dietaia.app.data.RegisterRequest
import com.dietaia.app.data.TipDto
import com.dietaia.app.data.WeeklyMenuDto
import kotlinx.coroutines.Job
import kotlinx.coroutines.delay
import kotlinx.coroutines.isActive
import kotlinx.coroutines.launch

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
        private set
    var message by mutableStateOf<String?>(null)
        private set
    var dashboard by mutableStateOf<DashboardResponse?>(null)
        private set
    var dietPlans by mutableStateOf<List<DietPlanDto>>(emptyList())
        private set
    var menu by mutableStateOf<WeeklyMenuDto?>(null)
        private set
    var tips by mutableStateOf<List<TipDto>>(emptyList())
        private set

    fun applyAuthToken(value: String) {
        token = value
        ApiClient.setToken(value)
    }

    fun clearFeedback() {
        error = null
        message = null
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

    fun login(email: String, password: String, onSuccess: (String) -> Unit) {
        viewModelScope.launch {
            beginBusy(listOf("Iniciando sesión…"))
            error = null
            try {
                val res = api.login(LoginRequest(email, password))
                applyAuthToken(res.token)
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
                api.logout()
            } catch (_: Exception) {
            }
            token = null
            ApiClient.setToken(null)
            onDone()
        }
    }

    fun loadDashboard() {
        viewModelScope.launch {
            beginBusy(listOf("Cargando tu día…"))
            error = null
            try {
                dashboard = api.dashboard()
            } catch (e: Exception) {
                error = e.message
            } finally {
                endBusy()
            }
        }
    }

    fun createMeal(description: String, mealType: String, onDone: () -> Unit) {
        viewModelScope.launch {
            beginBusy(
                listOf(
                    "Analizando la comida…",
                    "Estimando calorías y nutrientes…",
                    "Afinando la estimación…",
                    "Casi listo…",
                ),
            )
            error = null
            message = null
            try {
                api.createMeal(MealCreateRequest(description, mealType, true))
                message = "Comida registrada"
                onDone()
            } catch (e: Exception) {
                error = e.message
            } finally {
                endBusy()
            }
        }
    }

    fun deleteMeal(mealId: Int) {
        viewModelScope.launch {
            beginBusy(listOf("Eliminando comida…"))
            error = null
            message = null
            try {
                api.deleteMeal(mealId)
                message = "Comida eliminada"
                dashboard = api.dashboard()
            } catch (e: Exception) {
                error = e.message
            } finally {
                endBusy()
            }
        }
    }

    fun loadDiets() {
        viewModelScope.launch {
            beginBusy(listOf("Cargando planes…"))
            try {
                dietPlans = api.dietPlans()
            } catch (e: Exception) {
                error = e.message
            } finally {
                endBusy()
            }
        }
    }

    fun selectDiet(id: Int) {
        viewModelScope.launch {
            beginBusy(listOf("Activando plan…"))
            try {
                api.selectDiet(DietSelectRequest(id))
                message = "Plan seleccionado"
            } catch (e: Exception) {
                error = e.message
            } finally {
                endBusy()
            }
        }
    }

    fun suggestDiet() {
        viewModelScope.launch {
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
                error = e.message
            } finally {
                endBusy()
            }
        }
    }

    fun generateMenu(horizon: String) {
        viewModelScope.launch {
            beginBusy(
                listOf(
                    "Analizando tu plan y objetivos…",
                    if (horizon == "weekly") "Creando el menú semanal…" else "Creando el menú diario…",
                    "Equilibrando calorías y macros…",
                    "Casi listo…",
                ),
            )
            try {
                menu = api.generateMenu(MenuGenerateRequest(horizon))
            } catch (e: Exception) {
                error = e.message
            } finally {
                endBusy()
            }
        }
    }

    fun loadLatestMenu() {
        viewModelScope.launch {
            try {
                menu = api.latestMenu()
            } catch (_: Exception) {
            }
        }
    }

    fun loadTips(forceRefresh: Boolean = false) {
        viewModelScope.launch {
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
                error = e.message
            } finally {
                endBusy()
            }
        }
    }
}
