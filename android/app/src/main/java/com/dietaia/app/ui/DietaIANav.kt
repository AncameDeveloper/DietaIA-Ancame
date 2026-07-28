package com.dietaia.app.ui

import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.automirrored.filled.ArrowBack
import androidx.compose.material.icons.filled.AddAPhoto
import androidx.compose.material.icons.filled.AutoAwesome
import androidx.compose.material.icons.filled.CalendarMonth
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Lightbulb
import androidx.compose.material.icons.filled.Person
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.ExperimentalMaterial3Api
import androidx.compose.material3.FloatingActionButton
import androidx.compose.material3.Icon
import androidx.compose.material3.IconButton
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.material3.TopAppBar
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.dietaia.app.data.ApiClient
import com.dietaia.app.data.TokenStore
import com.dietaia.app.ui.screens.AiAssistantSheet
import com.dietaia.app.ui.screens.DashboardScreen
import com.dietaia.app.ui.screens.DietsScreen
import com.dietaia.app.ui.screens.LoginScreen
import com.dietaia.app.ui.screens.MealScreen
import com.dietaia.app.ui.screens.MenusScreen
import com.dietaia.app.ui.screens.ProfileScreen
import com.dietaia.app.ui.screens.ProgressScreen
import com.dietaia.app.ui.screens.RegisterScreen
import com.dietaia.app.ui.screens.TipsScreen
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch

private data class BottomTab(
    val route: String,
    val label: String,
    val icon: ImageVector,
)

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun DietaIANav(vm: AppViewModel = viewModel()) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val tokenStore = remember { TokenStore(context) }
    val savedToken by tokenStore.token.collectAsState(initial = null)
    val nav = rememberNavController()
    val loggedIn = vm.token != null
    var showAiAssistant by remember { mutableStateOf(false) }
    var sessionBootstrapped by remember { mutableStateOf(false) }

    LaunchedEffect(Unit) {
        val stored = runCatching { tokenStore.token.first() }.getOrNull()
        if (!stored.isNullOrBlank()) {
            ApiClient.setToken(stored)
            vm.applyAuthToken(stored)
        }
        sessionBootstrapped = true
    }

    LaunchedEffect(savedToken) {
        if (!sessionBootstrapped) return@LaunchedEffect
        if (!savedToken.isNullOrBlank() && vm.token == null) {
            ApiClient.setToken(savedToken)
            vm.applyAuthToken(savedToken!!)
        }
    }

    LaunchedEffect(vm.requireLogin) {
        if (vm.requireLogin) {
            tokenStore.clear()
            showAiAssistant = false
            nav.navigate("login") {
                popUpTo(0) { inclusive = true }
                launchSingleTop = true
            }
            vm.consumeRequireLogin()
        }
    }

    LaunchedEffect(loggedIn, sessionBootstrapped) {
        if (!sessionBootstrapped) return@LaunchedEffect
        val route = nav.currentBackStackEntry?.destination?.route
        if (loggedIn && route in setOf("login", "register", null)) {
            nav.navigate("dashboard") {
                popUpTo(0) { inclusive = true }
                launchSingleTop = true
            }
        }
    }

    if (!sessionBootstrapped) {
        Box(modifier = Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
            CircularProgressIndicator()
        }
        return
    }

    val bottomTabs = listOf(
        BottomTab("dashboard", "Hoy", Icons.Default.Home),
        BottomTab("meals", "Comida", Icons.Default.AddAPhoto),
        BottomTab("menus", "Menús", Icons.Default.CalendarMonth),
        BottomTab("tips", "Consejos", Icons.Default.Lightbulb),
    )
    val nestedRoutes = setOf("profile", "diets", "progress")
    val mainRoutes = bottomTabs.map { it.route }.toSet()

    val backStack by nav.currentBackStackEntryAsState()
    val currentRoute = backStack?.destination?.route
    val showChrome = loggedIn && currentRoute != null && currentRoute !in setOf("login", "register")
    val showFab = showChrome && currentRoute in mainRoutes
    val title = when (currentRoute) {
        "dashboard" -> "Hoy"
        "meals" -> "Comida"
        "menus" -> "Menús"
        "tips" -> "Consejos"
        "profile" -> "Perfil"
        "diets" -> "Dietas"
        "progress" -> "Progreso"
        else -> "DietaIA"
    }

    fun doLogout() {
        vm.logout {
            scope.launch { tokenStore.clear() }
            nav.navigate("login") { popUpTo(0) { inclusive = true } }
        }
    }

    Scaffold(
        topBar = {
            if (showChrome) {
                TopAppBar(
                    title = { Text(title) },
                    navigationIcon = {
                        if (currentRoute in nestedRoutes) {
                            IconButton(onClick = { nav.popBackStack() }) {
                                Icon(Icons.AutoMirrored.Filled.ArrowBack, contentDescription = "Volver")
                            }
                        }
                    },
                    actions = {
                        if (currentRoute != "profile") {
                            IconButton(onClick = { nav.navigate("profile") { launchSingleTop = true } }) {
                                Icon(Icons.Default.Person, contentDescription = "Perfil")
                            }
                        }
                    },
                )
            }
        },
        bottomBar = {
            if (showChrome) {
                NavigationBar {
                    bottomTabs.forEach { tab ->
                        NavigationBarItem(
                            selected = currentRoute == tab.route,
                            onClick = {
                                nav.navigate(tab.route) {
                                    popUpTo(nav.graph.findStartDestination().id) { saveState = true }
                                    launchSingleTop = true
                                    restoreState = true
                                }
                            },
                            icon = { Icon(tab.icon, contentDescription = tab.label) },
                            label = { Text(tab.label) },
                            alwaysShowLabel = true,
                        )
                    }
                }
            }
        },
        floatingActionButton = {
            if (showFab) {
                FloatingActionButton(
                    onClick = {
                        vm.clearFeedback()
                        showAiAssistant = true
                    },
                ) {
                    Icon(Icons.Default.AutoAwesome, contentDescription = "Asistente IA")
                }
            }
        },
    ) { padding ->
        NavHost(
            navController = nav,
            startDestination = if (loggedIn) "dashboard" else "login",
            modifier = Modifier.padding(padding),
        ) {
            composable("login") {
                LoginScreen(
                    onLogin = { email, password ->
                        vm.login(email, password) { token ->
                            scope.launch { tokenStore.save(token) }
                            nav.navigate("dashboard") { popUpTo("login") { inclusive = true } }
                        }
                    },
                    onGoogleLogin = { idToken ->
                        vm.loginWithGoogle(idToken) { token ->
                            scope.launch { tokenStore.save(token) }
                            nav.navigate("dashboard") { popUpTo("login") { inclusive = true } }
                        }
                    },
                    onGoRegister = { nav.navigate("register") },
                    error = vm.error,
                    loading = vm.loading,
                )
            }
            composable("register") {
                RegisterScreen(
                    onRegister = { name, email, password ->
                        vm.register(name, email, password) { token ->
                            scope.launch { tokenStore.save(token) }
                            nav.navigate("dashboard") { popUpTo("login") { inclusive = true } }
                        }
                    },
                    onGoogleLogin = { idToken ->
                        vm.loginWithGoogle(idToken) { token ->
                            scope.launch { tokenStore.save(token) }
                            nav.navigate("dashboard") { popUpTo("login") { inclusive = true } }
                        }
                    },
                    onBack = { nav.popBackStack() },
                    error = vm.error,
                    loading = vm.loading,
                )
            }
            composable("dashboard") {
                LaunchedEffect(Unit) { vm.loadDashboard() }
                DashboardScreen(
                    state = vm.dashboard,
                    selectedDate = vm.selectedDate,
                    micronutrients = vm.micronutrients,
                    microRange = vm.microRange,
                    microGroup = vm.microGroup,
                    loading = vm.loading,
                    error = vm.error,
                    softNotice = vm.softNotice,
                    onRefresh = { vm.loadDashboard() },
                    onPrevDay = { vm.shiftSelectedDate(-1) },
                    onNextDay = { vm.shiftSelectedDate(1) },
                    onPickDate = { vm.updateSelectedDate(it) },
                    onOpenProgress = { nav.navigate("progress") { launchSingleTop = true } },
                    onMicroRange = { vm.updateMicroRange(it) },
                    onMicroGroup = { vm.updateMicroGroup(it) },
                    onDeleteMeal = { vm.deleteMeal(it) },
                )
            }
            composable("meals") {
                MealScreen(
                    loading = vm.loading,
                    busyLabel = vm.busyLabel,
                    error = vm.error,
                    message = vm.message,
                    onSubmitText = { text, type ->
                        vm.createMeal(text, type) { }
                    },
                    onSubmitPhoto = { uri, type ->
                        vm.analyzeMealPhoto(context, uri, type) {
                            vm.loadDashboard()
                        }
                    },
                )
            }
            composable("menus") {
                LaunchedEffect(Unit) { vm.loadLatestMenu() }
                MenusScreen(
                    dailyMenu = vm.dailyMenu,
                    weeklyMenu = vm.weeklyMenu,
                    loading = vm.loading,
                    busyLabel = vm.busyLabel,
                    shoppingItems = vm.shoppingItems,
                    shoppingError = vm.shoppingError,
                    onGenerate = { vm.generateMenu(it) },
                    onLoad = { vm.loadLatestMenu(it) },
                    onOpenShoppingList = { horizon, menu -> vm.loadShoppingList(horizon, menu) },
                    onCloseShoppingList = { vm.clearShoppingList() },
                )
            }
            composable("tips") {
                LaunchedEffect(Unit) { vm.loadTips(forceRefresh = false) }
                TipsScreen(
                    tips = vm.tips,
                    loading = vm.loading,
                    busyLabel = vm.busyLabel,
                    onLoad = { vm.loadTips(forceRefresh = false) },
                    onRefresh = { vm.loadTips(forceRefresh = true) },
                )
            }
            composable("profile") {
                LaunchedEffect(Unit) { vm.loadProfile() }
                ProfileScreen(
                    user = vm.currentUser,
                    dashboard = vm.dashboard,
                    softNotice = vm.softNotice,
                    onOpenDiets = { nav.navigate("diets") { launchSingleTop = true } },
                    onOpenProgress = { nav.navigate("progress") { launchSingleTop = true } },
                    onLogout = { doLogout() },
                )
            }
            composable("diets") {
                LaunchedEffect(Unit) { vm.loadDiets() }
                DietsScreen(
                    plans = vm.dietPlans,
                    loading = vm.loading,
                    busyLabel = vm.busyLabel,
                    message = vm.message,
                    onLoad = { vm.loadDiets() },
                    onSelect = { vm.selectDiet(it) },
                    onSuggest = { vm.suggestDiet() },
                )
            }
            composable("progress") {
                LaunchedEffect(Unit) { vm.loadWeightProgress() }
                ProgressScreen(
                    progress = vm.weightProgress,
                    loading = vm.loading,
                    softNotice = vm.softNotice,
                    onReload = { vm.loadWeightProgress(it) },
                )
            }
        }

        AiAssistantSheet(
            visible = showAiAssistant,
            loading = vm.loading,
            busyLabel = vm.busyLabel,
            message = vm.message,
            error = vm.error,
            onDismiss = { showAiAssistant = false },
            onRegisterMeal = { description ->
                vm.createMeal(description, "lunch") {
                    showAiAssistant = false
                    nav.navigate("dashboard") { launchSingleTop = true }
                    vm.loadDashboard()
                }
            },
            onOpenMeals = {
                showAiAssistant = false
                nav.navigate("meals") { launchSingleTop = true }
            },
            onOpenTips = {
                showAiAssistant = false
                nav.navigate("tips") { launchSingleTop = true }
            },
            onSuggestDiet = {
                vm.suggestDiet()
                showAiAssistant = false
                nav.navigate("diets") { launchSingleTop = true }
            },
        )
    }
}
