package com.dietaia.app.data

import okhttp3.MultipartBody
import okhttp3.RequestBody
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part
import retrofit2.http.Path
import retrofit2.http.PUT
import retrofit2.http.Query

interface DietaApi {
    @POST("register")
    suspend fun register(@Body body: RegisterRequest): AuthResponse

    @POST("login")
    suspend fun login(@Body body: LoginRequest): AuthResponse

    @POST("auth/google")
    suspend fun loginWithGoogle(@Body body: GoogleAuthRequest): AuthResponse

    @POST("logout")
    suspend fun logout()

    @GET("dashboard/today")
    suspend fun dashboard(): DashboardResponse

    @GET("diet-plans")
    suspend fun dietPlans(): List<DietPlanDto>

    @POST("diet-plans/select")
    suspend fun selectDiet(@Body body: DietSelectRequest): Any

    @POST("diet-plans/suggest")
    suspend fun suggestDiet(): Map<String, Any>

    @POST("meals")
    suspend fun createMeal(@Body body: MealCreateRequest): MealCreateResponse

    @DELETE("meals/{id}")
    suspend fun deleteMeal(@Path("id") id: Int): Map<String, String>

    @Multipart
    @POST("meals/analyze-photo")
    suspend fun analyzePhoto(
        @Part photo: MultipartBody.Part,
        @Part("meal_type") mealType: RequestBody,
        @Part("confirm") confirm: RequestBody,
    ): Map<String, Any>

    @POST("menus/generate")
    suspend fun generateMenu(@Body body: MenuGenerateRequest): WeeklyMenuDto

    @GET("menus/latest")
    suspend fun latestMenu(@Query("horizon") horizon: String? = null): WeeklyMenuDto?

    @POST("menus/shopping-list")
    suspend fun shoppingList(@Body body: ShoppingListRequest): ShoppingListResponse

    @GET("tips")
    suspend fun tips(@Query("refresh") refresh: Boolean = false): TipsResponse

    @PUT("profile")
    suspend fun updateProfile(@Body body: ProfileUpdateRequest): UserDto
}
