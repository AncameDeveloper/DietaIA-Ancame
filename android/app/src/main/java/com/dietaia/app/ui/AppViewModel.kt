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
import com.dietaia.app.data.LoginRequest
import com.dietaia.app.data.MealCreateRequest
import com.dietaia.app.data.MenuGenerateRequest
import com.dietaia.app.data.RegisterRequest
import com.dietaia.app.data.TipDto
import com.dietaia.app.data.WeeklyMenuDto
import kotlinx.coroutines.launch

class AppViewModel : ViewModel() {
    private val api get() = ApiClient.create()

    var token by mutableStateOf<String?>(null)
        private set
    var loading by mutableStateOf(false)
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

    fun setToken(value: String) {
        token = value
        ApiClient.setToken(value)
    }

    fun login(email: String, password: String, onSuccess: (String) -> Unit) {
        viewModelScope.launch {
            loading = true
            error = null
            try {
                val res = api.login(LoginRequest(email, password))
                setToken(res.token)
                onSuccess(res.token)
            } catch (e: Exception) {
                error = e.message ?: "Error de login"
            } finally {
                loading = false
            }
        }
    }

    fun register(name: String, email: String, password: String, onSuccess: (String) -> Unit) {
        viewModelScope.launch {
            loading = true
            error = null
            try {
                val res = api.register(RegisterRequest(name, email, password, password))
                setToken(res.token)
                onSuccess(res.token)
            } catch (e: Exception) {
                error = e.message ?: "Error de registro"
            } finally {
                loading = false
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
            loading = true
            error = null
            try {
                dashboard = api.dashboard()
            } catch (e: Exception) {
                error = e.message
            } finally {
                loading = false
            }
        }
    }

    fun createMeal(description: String, mealType: String, onDone: () -> Unit) {
        viewModelScope.launch {
            loading = true
            error = null
            message = null
            try {
                api.createMeal(MealCreateRequest(description, mealType, true))
                message = "Comida registrada"
                onDone()
            } catch (e: Exception) {
                error = e.message
            } finally {
                loading = false
            }
        }
    }

    fun loadDiets() {
        viewModelScope.launch {
            loading = true
            try {
                dietPlans = api.dietPlans()
            } catch (e: Exception) {
                error = e.message
            } finally {
                loading = false
            }
        }
    }

    fun selectDiet(id: Int) {
        viewModelScope.launch {
            loading = true
            try {
                api.selectDiet(DietSelectRequest(id))
                message = "Plan seleccionado"
            } catch (e: Exception) {
                error = e.message
            } finally {
                loading = false
            }
        }
    }

    fun suggestDiet() {
        viewModelScope.launch {
            loading = true
            try {
                api.suggestDiet()
                message = "Plan sugerido por IA"
                loadDiets()
            } catch (e: Exception) {
                error = e.message
            } finally {
                loading = false
            }
        }
    }

    fun generateMenu(horizon: String) {
        viewModelScope.launch {
            loading = true
            try {
                menu = api.generateMenu(MenuGenerateRequest(horizon))
            } catch (e: Exception) {
                error = e.message
            } finally {
                loading = false
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

    fun loadTips() {
        viewModelScope.launch {
            loading = true
            try {
                tips = api.tips().tips
            } catch (e: Exception) {
                error = e.message
            } finally {
                loading = false
            }
        }
    }
}
