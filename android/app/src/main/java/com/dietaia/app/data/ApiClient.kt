package com.dietaia.app.data

import com.dietaia.app.BuildConfig
import okhttp3.Interceptor
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import org.json.JSONObject
import retrofit2.HttpException
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.io.IOException
import java.util.concurrent.TimeUnit
import java.util.concurrent.atomic.AtomicReference

object ApiClient {
    @Volatile
    private var token: String? = null

    private val unauthorizedHandler = AtomicReference<(() -> Unit)?>(null)

    private val api: DietaApi by lazy { buildApi() }

    fun setToken(value: String?) {
        token = value
    }

    fun currentToken(): String? = token

    fun setUnauthorizedHandler(handler: (() -> Unit)?) {
        unauthorizedHandler.set(handler)
    }

    fun create(): DietaApi = api

    private fun buildApi(): DietaApi {
        val auth = Interceptor { chain ->
            val original = chain.request()
            val builder = original.newBuilder()
                .header("Accept", "application/json")
            // No forzar Content-Type: rompe multipart (foto).

            token?.takeIf { it.isNotBlank() }?.let {
                builder.header("Authorization", "Bearer $it")
            }

            val response = chain.proceed(builder.build())
            if (response.code == 401) {
                val path = original.url.encodedPath.lowercase()
                val isAuthEndpoint = path.contains("/login") ||
                    path.contains("/register") ||
                    path.contains("/auth/google")
                if (!isAuthEndpoint) {
                    token = null
                    unauthorizedHandler.get()?.invoke()
                }
            }
            response
        }

        val logging = HttpLoggingInterceptor().apply {
            level = if (BuildConfig.DEBUG) {
                HttpLoggingInterceptor.Level.BODY
            } else {
                HttpLoggingInterceptor.Level.BASIC
            }
        }

        val client = OkHttpClient.Builder()
            .addInterceptor(auth)
            .addInterceptor(logging)
            .connectTimeout(60, TimeUnit.SECONDS)
            .readTimeout(60, TimeUnit.SECONDS)
            .writeTimeout(60, TimeUnit.SECONDS)
            .build()

        return Retrofit.Builder()
            .baseUrl(BuildConfig.API_BASE_URL)
            .client(client)
            .addConverterFactory(GsonConverterFactory.create())
            .build()
            .create(DietaApi::class.java)
    }

    fun humanizeError(error: Throwable): String {
        return when (error) {
            is HttpException -> {
                if (error.code() == 401) {
                    return "Sesión caducada. Vuelve a iniciar sesión."
                }
                val body = error.response()?.errorBody()?.string().orEmpty()
                val parsed = parseLaravelMessage(body)
                when (error.code()) {
                    404 -> friendlyNotFound(parsed)
                    422 -> parsed ?: "Datos no válidos."
                    429 -> parsed ?: "Demasiadas peticiones. Espera un momento."
                    500, 502, 503 -> parsed?.takeUnless { isRawFrameworkMessage(it) }
                        ?: "Error del servidor. Inténtalo más tarde."
                    else -> parsed?.takeUnless { isRawFrameworkMessage(it) }
                        ?: "No se pudo completar la acción (${error.code()})."
                }
            }
            is IOException -> "Sin conexión con el servidor."
            else -> error.message?.takeUnless { isRawFrameworkMessage(it) } ?: "Error desconocido"
        }
    }

    private fun friendlyNotFound(parsed: String?): String {
        if (parsed.isNullOrBlank() || isRawFrameworkMessage(parsed)) {
            return "Esa información no está disponible ahora."
        }
        return parsed
    }

    private fun isRawFrameworkMessage(message: String): Boolean {
        val lower = message.lowercase()
        return lower.contains("could not be found") ||
            (lower.contains("route") && lower.contains("not found")) ||
            lower.startsWith("sqlstate")
    }

    private fun parseLaravelMessage(body: String): String? {
        if (body.isBlank()) return null
        return try {
            val json = JSONObject(body)
            when {
                json.has("message") && json.opt("message") is String -> json.getString("message")
                json.has("errors") -> {
                    val errors = json.getJSONObject("errors")
                    val key = errors.keys().asSequence().firstOrNull() ?: return json.optString("message")
                    val arr = errors.optJSONArray(key)
                    arr?.optString(0)?.takeIf { it.isNotBlank() }
                        ?: errors.optString(key).takeIf { it.isNotBlank() }
                }
                else -> null
            }
        } catch (_: Exception) {
            null
        }
    }
}
