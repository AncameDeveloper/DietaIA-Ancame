package com.dietaia.app.ui.screens

import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Spacer
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Button
import androidx.compose.material3.CircularProgressIndicator
import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.OutlinedButton
import androidx.compose.material3.OutlinedTextField
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.text.input.PasswordVisualTransformation
import androidx.compose.ui.unit.dp
import com.dietaia.app.auth.GoogleAuthHelper
import kotlinx.coroutines.launch

@Composable
fun LoginScreen(
    onLogin: (String, String) -> Unit,
    onGoogleLogin: (String) -> Unit,
    onGoRegister: () -> Unit,
    error: String?,
    loading: Boolean,
) {
    var email by remember { mutableStateOf("demo@dietaia.test") }
    var password by remember { mutableStateOf("password") }
    var googleError by remember { mutableStateOf<String?>(null) }
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val googleAuth = remember { GoogleAuthHelper(context) }

    Column(
        modifier = Modifier.fillMaxSize().padding(24.dp),
        verticalArrangement = Arrangement.Center,
        horizontalAlignment = Alignment.CenterHorizontally,
    ) {
        Text("DietaIA", style = MaterialTheme.typography.headlineLarge, color = MaterialTheme.colorScheme.primary)
        Text("Seguimiento nutricional con IA", style = MaterialTheme.typography.bodyMedium)
        Spacer(Modifier.height(24.dp))

        OutlinedButton(
            onClick = {
                googleError = null
                scope.launch {
                    googleAuth.signIn()
                        .onSuccess(onGoogleLogin)
                        .onFailure { err ->
                            googleError = err.message ?: "Error de Google Sign-In"
                        }
                }
            },
            enabled = !loading,
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text("Continuar con Google")
        }

        Spacer(Modifier.height(16.dp))
        Text("o con email", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Spacer(Modifier.height(8.dp))

        OutlinedTextField(value = email, onValueChange = { email = it }, label = { Text("Email") }, modifier = Modifier.fillMaxWidth())
        Spacer(Modifier.height(8.dp))
        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Contraseña") },
            visualTransformation = PasswordVisualTransformation(),
            modifier = Modifier.fillMaxWidth(),
        )
        val displayError = googleError ?: error
        if (displayError != null) {
            Spacer(Modifier.height(8.dp))
            Text(displayError, color = MaterialTheme.colorScheme.error)
        }
        Spacer(Modifier.height(16.dp))
        Button(onClick = { onLogin(email, password) }, enabled = !loading, modifier = Modifier.fillMaxWidth()) {
            if (loading) CircularProgressIndicator() else Text("Entrar")
        }
        TextButton(onClick = onGoRegister) { Text("Crear cuenta") }
    }
}

@Composable
fun RegisterScreen(
    onRegister: (String, String, String) -> Unit,
    onGoogleLogin: (String) -> Unit,
    onBack: () -> Unit,
    error: String?,
    loading: Boolean,
) {
    var name by remember { mutableStateOf("") }
    var email by remember { mutableStateOf("") }
    var password by remember { mutableStateOf("") }
    var googleError by remember { mutableStateOf<String?>(null) }
    val context = LocalContext.current
    val scope = rememberCoroutineScope()
    val googleAuth = remember { GoogleAuthHelper(context) }

    Column(modifier = Modifier.fillMaxSize().padding(24.dp), verticalArrangement = Arrangement.Center) {
        Text("Crear cuenta", style = MaterialTheme.typography.headlineMedium)
        Spacer(Modifier.height(16.dp))

        OutlinedButton(
            onClick = {
                googleError = null
                scope.launch {
                    googleAuth.signIn()
                        .onSuccess(onGoogleLogin)
                        .onFailure { err ->
                            googleError = err.message ?: "Error de Google Sign-In"
                        }
                }
            },
            enabled = !loading,
            modifier = Modifier.fillMaxWidth(),
        ) {
            Text("Continuar con Google")
        }

        Spacer(Modifier.height(16.dp))
        Text("o con email", style = MaterialTheme.typography.bodySmall, color = MaterialTheme.colorScheme.onSurfaceVariant)
        Spacer(Modifier.height(8.dp))

        OutlinedTextField(value = name, onValueChange = { name = it }, label = { Text("Nombre") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(value = email, onValueChange = { email = it }, label = { Text("Email") }, modifier = Modifier.fillMaxWidth())
        OutlinedTextField(
            value = password,
            onValueChange = { password = it },
            label = { Text("Contraseña") },
            visualTransformation = PasswordVisualTransformation(),
            modifier = Modifier.fillMaxWidth(),
        )
        val displayError = googleError ?: error
        if (displayError != null) Text(displayError, color = MaterialTheme.colorScheme.error)
        Spacer(Modifier.height(12.dp))
        Button(onClick = { onRegister(name, email, password) }, enabled = !loading, modifier = Modifier.fillMaxWidth()) {
            Text("Registrarme")
        }
        TextButton(onClick = onBack) { Text("Volver") }
    }
}
