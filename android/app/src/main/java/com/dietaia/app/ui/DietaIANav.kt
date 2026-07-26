package com.dietaia.app.ui

import androidx.compose.foundation.layout.padding
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Add
import androidx.compose.material.icons.filled.Home
import androidx.compose.material.icons.filled.Lightbulb
import androidx.compose.material.icons.filled.Restaurant
import androidx.compose.material3.Icon
import androidx.compose.material3.NavigationBar
import androidx.compose.material3.NavigationBarItem
import androidx.compose.material3.Scaffold
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.collectAsState
import androidx.compose.runtime.getValue
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.ui.Modifier.modifier
import androidx.compose.ui.platform.LocalContext
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavGraph.Companion.findStartDestination
import androidx.navigation.compose.NavHost
import androidx.navigation.compose.composable
import androidx.navigation.compose.currentBackStackEntryAsState
import androidx.navigation.compose.rememberNavController
import com.dietaia.app.data.ApiClient
import com.dietaia.app.data.TokenStore
import com.dietaia.app.ui.screens.DashboardScreen
import com.dietaia.app.ui.screens.DietsScreen
import com.dietaia.app.ui.screens.LoginScreen
import com.dietaia.app.ui.screens.MealScreen
import com.dietaia.app.ui.screens.MenusScreen
import com.dietaia.app.ui.screens.RegisterScreen
import com.dietaia.app.ui.screens.TipsScreen
import kotlinx.coroutines.launch

@Composable
fun DietaIANav(vm: AppViewModel = viewModel()) {
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val tokenStore = remember { TokenStore(context) }
    val savedToken by tokenStore.token.collectAsState(initial = null)
    val nav = rememberNavController()
    val loggedIn = vm.token != null

    LaunchedEffect(savedToken) {
        if (!savedToken.isNullOrBlank() && vm.token == null) {
            ApiClient.setToken(savedToken)
            vm.setToken(savedToken!!)
        }
    }

    val items = listOf(
        Triple("dashboard", "Hoy", Icons.Default.Home),
        Triple("meals", "Comida", Icons.Default.Add),
        Triple("diets", "Dietas", Icons.Default.Restaurant),
        Triple("menus", "Menús", Icons.Default.Restaurant),
        Triple("tips", "Tips", Icons.Default.Lightbulb),
    )

    Scaffold(
        bottomBar = {
            if (loggedIn) {
                val backStack by nav.currentBackStackEntryAsState()
                val current = backStack?.destination?.route
                NavigationBar {
                    items.forEach { (route, label, icon) ->
                        NavigationBarItem(
                            selected = current == route,
                            onClick = {
                                nav.navigate(route) {
                                    popUpTo(nav.graph.findStartDestination().id) { saveState = true }
                                    launchSingleTop = true
                                    restoreState = true
                                }
                            },
                            icon = { Icon(icon, contentDescription = label) },
                            label = { Text(label) },
                        )
                    }
                }
            }
        }
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
                    onBack = { nav.popBackStack() },
                    error = vm.error,
                    loading = vm.loading,
                )
            }
            composable("dashboard") {
                LaunchedEffect(Unit) { vm.loadDashboard() }
                DashboardScreen(
                    state = vm.dashboard,
                    loading = vm.loading,
                    error = vm.error,
                    onRefresh = { vm.loadDashboard() },
                    onLogout = {
                        vm.logout {
                            scope.launch { tokenStore.clear() }
                            nav.navigate("login") { popUpTo(0) }
                        }
                    },
                )
            }
            composable("meals") {
                MealScreen(
                    loading = vm.loading,
                    error = vm.error,
                    message = vm.message,
                    onSubmitText = { text, type ->
                        vm.createMeal(text, type) { }
                    },
                )
            }
            composable("diets") {
                LaunchedEffect(Unit) { vm.loadDiets() }
                DietsScreen(
                    plans = vm.dietPlans,
                    loading = vm.loading,
                    message = vm.message,
                    onLoad = { vm.loadDiets() },
                    onSelect = { vm.selectDiet(it) },
                    onSuggest = { vm.suggestDiet() },
                )
            }
            composable("menus") {
                LaunchedEffect(Unit) { vm.loadLatestMenu() }
                MenusScreen(
                    menu = vm.menu,
                    loading = vm.loading,
                    onGenerate = { vm.generateMenu(it) },
                    onLoad = { vm.loadLatestMenu() },
                )
            }
            composable("tips") {
                LaunchedEffect(Unit) { vm.loadTips() }
                TipsScreen(tips = vm.tips, loading = vm.loading, onLoad = { vm.loadTips() })
            }
        }
    }
}
