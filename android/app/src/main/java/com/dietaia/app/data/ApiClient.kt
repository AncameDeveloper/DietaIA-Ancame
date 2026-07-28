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

object ApiClient {
    @Volatile
    private var token: String? = null

    fun setToken(value: String?) {
        token = value
    }

    fun create(): DietaApi {
        val auth = Interceptor { chain ->
            val builder = chain.request().newBuilder()
                .header("Accept", "application/json")
                .header("Content-Type", "application/json")
            token?.let { builder.header("Authorization", "Bearer $it") }
            chain.proceed(builder.build())
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
                val body = error.response()?.errorBody()?.string().orEmpty()
                parseLaravelMessage(body)
                    ?: when (error.code()) {
                        401, 422 -> "Credenciales incorrectas o datos no válidos."
                        404 -> "Endpoint no encontrado (${BuildConfig.API_BASE_URL})."
                        500 -> "Error del servidor. Inténtalo más tarde."
                        else -> "Error HTTP ${error.code()}"
                    }
            }
            is IOException -> "Sin conexión con ${BuildConfig.API_BASE_URL}"
            else -> error.message ?: "Error desconocido"
        }
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
