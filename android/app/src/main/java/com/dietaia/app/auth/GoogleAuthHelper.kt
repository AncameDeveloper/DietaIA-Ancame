package com.dietaia.app.auth

import android.app.Activity
import android.content.Context
import android.content.ContextWrapper
import android.util.Log
import android.widget.Toast
import androidx.credentials.CredentialManager
import androidx.credentials.CustomCredential
import androidx.credentials.GetCredentialRequest
import androidx.credentials.exceptions.GetCredentialCancellationException
import androidx.credentials.exceptions.GetCredentialException
import androidx.credentials.exceptions.NoCredentialException
import com.dietaia.app.BuildConfig
import com.google.android.libraries.identity.googleid.GetGoogleIdOption
import com.google.android.libraries.identity.googleid.GetSignInWithGoogleOption
import com.google.android.libraries.identity.googleid.GoogleIdTokenCredential
import com.google.android.libraries.identity.googleid.GoogleIdTokenParsingException
import java.util.UUID

/**
 * Google Sign-In via Credential Manager.
 *
 * - Cliente OAuth **Android** (package + SHA-1) debe existir en Google Cloud.
 * - [setServerClientId] debe ser el Client ID **Web** (audience del id_token / Laravel).
 */
class GoogleAuthHelper(private val context: Context) {
    private val credentialManager = CredentialManager.create(context)
    private val activityContext: Context
        get() = context.findActivity() ?: context

    suspend fun signIn(): Result<String> {
        val serverClientId = BuildConfig.GOOGLE_WEB_CLIENT_ID.trim()
        if (serverClientId.isBlank()) {
            val msg = "GOOGLE_WEB_CLIENT_ID vacío. Configúralo en local.properties y vuelve a sincronizar Gradle."
            Log.e(TAG, msg)
            showToast(msg)
            return Result.failure(IllegalStateException(msg))
        }

        Log.i(
            TAG,
            "Google Sign-In start. API=${BuildConfig.API_BASE_URL} webClientId=${serverClientId.take(28)}…",
        )

        val oneTap = runCatching { requestGoogleIdToken(serverClientId) }
        if (oneTap.isSuccess) return oneTap

        val oneTapError = oneTap.exceptionOrNull()
        Log.w(TAG, "One Tap falló: ${formatError(oneTapError)}. Probando Sign-In With Google.")

        return runCatching { requestSignInWithGoogle(serverClientId) }
            .onFailure { second ->
                val mapped = mapGoogleError(oneTapError ?: second)
                Log.e(TAG, "Google Sign-In FAILED: ${formatError(mapped)}", mapped)
                showToast(mapped.message ?: formatError(mapped))
            }
            .recoverCatching { second ->
                throw mapGoogleError(oneTapError ?: second)
            }
    }

    private suspend fun requestGoogleIdToken(serverClientId: String): String {
        val googleIdOption = GetGoogleIdOption.Builder()
            .setFilterByAuthorizedAccounts(false)
            .setServerClientId(serverClientId)
            .setAutoSelectEnabled(false)
            .setNonce(UUID.randomUUID().toString())
            .build()

        val request = GetCredentialRequest.Builder()
            .addCredentialOption(googleIdOption)
            .build()

        return extractIdToken(
            credentialManager.getCredential(request = request, context = activityContext).credential,
        )
    }

    private suspend fun requestSignInWithGoogle(serverClientId: String): String {
        val option = GetSignInWithGoogleOption.Builder(serverClientId)
            .setNonce(UUID.randomUUID().toString())
            .build()

        val request = GetCredentialRequest.Builder()
            .addCredentialOption(option)
            .build()

        return extractIdToken(
            credentialManager.getCredential(request = request, context = activityContext).credential,
        )
    }

    private fun extractIdToken(credential: androidx.credentials.Credential): String {
        if (credential is CustomCredential &&
            credential.type == GoogleIdTokenCredential.TYPE_GOOGLE_ID_TOKEN_CREDENTIAL
        ) {
            return GoogleIdTokenCredential.createFrom(credential.data).idToken
        }
        throw IllegalStateException("Credencial de Google no válida (${credential::class.java.simpleName}).")
    }

    private fun mapGoogleError(error: Throwable): Exception {
        val detail = formatError(error)
        return when (error) {
            is GetCredentialCancellationException ->
                IllegalStateException("Inicio de sesión cancelado.")
            is NoCredentialException ->
                IllegalStateException(
                    "Sin cuentas Google ($detail). Usa emulador con Google Play, añade una cuenta " +
                        "y registra el SHA-1 debug en el cliente OAuth Android.",
                )
            is GoogleIdTokenParsingException ->
                IllegalStateException("No se pudo leer el id_token de Google.")
            is GetCredentialException ->
                IllegalStateException("Google error: $detail")
            is Exception ->
                IllegalStateException(error.message ?: detail, error)
            else ->
                Exception(detail, error)
        }
    }

    private fun formatError(error: Throwable?): String {
        if (error == null) return "unknown"
        val type = (error as? GetCredentialException)?.type
        val code = extractNumericCode(error.message)
        return buildString {
            append(error::class.java.simpleName)
            if (!type.isNullOrBlank()) append(" type=$type")
            if (!code.isNullOrBlank()) append(" code=$code")
            error.message?.let { append(" msg=$it") }
        }
    }

    private fun extractNumericCode(message: String?): String? {
        if (message.isNullOrBlank()) return null
        // Ejemplos: "10:", "12500:", "[28433]", "errorCode=10"
        val patterns = listOf(
            Regex("""\berrorCode[=:]?\s*(\d+)""", RegexOption.IGNORE_CASE),
            Regex("""\b(10|7|8|12500|12501|16)\b"""),
            Regex("""\[(\d{2,5})]"""),
            Regex(""":\s*(\d{1,5})\s*:"""),
        )
        for (pattern in patterns) {
            val match = pattern.find(message) ?: continue
            return match.groupValues.last { it.isNotBlank() && it.any(Char::isDigit) }
        }
        return null
    }

    private fun showToast(message: String) {
        val text = if (message.length > 180) message.take(177) + "…" else message
        val appCtx = context.applicationContext
        android.os.Handler(android.os.Looper.getMainLooper()).post {
            Toast.makeText(appCtx, text, Toast.LENGTH_LONG).show()
        }
    }

    companion object {
        private const val TAG = "GoogleAuthHelper"
    }
}

private fun Context.findActivity(): Activity? {
    var current: Context? = this
    while (current is ContextWrapper) {
        if (current is Activity) return current
        current = current.baseContext
    }
    return null
}
