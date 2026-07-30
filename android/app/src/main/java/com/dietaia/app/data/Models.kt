package com.dietaia.app.data

data class AuthResponse(
    val user: UserDto,
    val token: String,
)

data class UserDto(
    val id: Int,
    val name: String,
    val email: String,
    val profile: ProfileDto? = null,
)

data class ProfileDto(
    val age: Int? = null,
    val sex: String? = null,
    val weight_kg: Double? = null,
    val height_cm: Double? = null,
    val activity_level: String? = null,
    val goal: String? = null,
    val calorie_target: Int? = null,
    val protein_target_g: Int? = null,
    val carbs_target_g: Int? = null,
    val fat_target_g: Int? = null,
    val onboarding_completed: Boolean? = null,
)

data class DietPlanDto(
    val id: Int,
    val slug: String,
    val name: String,
    val description: String,
)

data class MealDto(
    val id: Int,
    val title: String?,
    val meal_type: String,
    val calories: Double,
    val protein_g: Double,
    val carbs_g: Double,
    val fat_g: Double,
    val source: String,
    val confirmed: Boolean,
)

data class DashboardResponse(
    val date: String,
    val profile: ProfileDto?,
    val diet: DietPlanDto?,
    val summary: SummaryDto?,
    val targets: TargetsDto?,
    val remaining: TargetsDto?,
    val meals: List<MealDto>,
    val disclaimer: String?,
)

data class SummaryDto(
    val calories: Double,
    val protein_g: Double,
    val carbs_g: Double,
    val fat_g: Double,
    val micros: Map<String, Double>? = null,
)

data class MicronutrientsResponse(
    val range: String,
    val from: String,
    val to: String,
    val days_counted: Int,
    val micros: Map<String, Double>? = null,
    val items: List<MicronutrientItemDto> = emptyList(),
    val groups: Map<String, String> = emptyMap(),
    val info: String? = null,
)

data class MicronutrientItemDto(
    val key: String,
    val label: String,
    val group: String,
    val value: Double,
    val target: Double,
    val unit: String,
    val pct: Double,
)

data class WeightProgressResponse(
    val days: Int = 90,
    val count: Int = 0,
    val min: Double = 0.0,
    val max: Double = 0.0,
    val items: List<WeightPointDto> = emptyList(),
)

data class WeightPointDto(
    val date: String,
    val weight: Double,
)

data class WeightLogRequest(
    val weight: Double,
    val date: String? = null,
    val note: String? = null,
)

data class WeightLogResponse(
    val message: String? = null,
    val log: WeightLogDto? = null,
    val items: List<WeightPointDto> = emptyList(),
)

data class WeightLogDto(
    val date: String,
    val weight: Double,
    val note: String? = null,
)

data class TargetsDto(
    val calories: Double? = null,
    val protein_g: Double? = null,
    val carbs_g: Double? = null,
    val fat_g: Double? = null,
)

data class WeeklyMenuDto(
    val id: Int,
    val horizon: String,
    val notes: String?,
    val content: MenuContentDto?,
)

data class MenuContentDto(
    val notes: String? = null,
    val days: List<MenuDayDto>? = null,
)

data class MenuDayDto(
    val day: Int? = null,
    val date_label: String? = null,
    val meals: List<MenuMealDto>? = null,
)

data class MenuMealDto(
    val meal_type: String? = null,
    val title: String? = null,
    val description: String? = null,
    val calories: Double? = null,
    val ingredients: List<MenuIngredientDto>? = null,
    val items: List<MenuIngredientDto>? = null,
)

data class MenuIngredientDto(
    val name: String? = null,
    val quantity_g: Double? = null,
)

data class ShoppingListRequest(
    val menu_id: Int? = null,
    val horizon: String? = null,
    val content: MenuContentDto? = null,
)

data class ShoppingListResponse(
    val menu_id: Int? = null,
    val horizon: String? = null,
    val count: Int = 0,
    val items: List<ShoppingListItemDto> = emptyList(),
)

data class ShoppingListItemDto(
    val name: String,
    val quantity_g: Double? = null,
    val unit: String? = null,
    val quantity_label: String? = null,
    val sources: List<String>? = null,
)

data class TipsResponse(
    val tips: List<TipDto>,
    val disclaimer: String? = null,
    val cached: Boolean? = null,
)

data class TipDto(
    val title: String?,
    val body: String?,
)

data class LoginRequest(val email: String, val password: String)

data class GoogleAuthRequest(val id_token: String)

data class RegisterRequest(
    val name: String,
    val email: String,
    val password: String,
    val password_confirmation: String,
)

data class MealCreateRequest(
    val description: String,
    val meal_type: String = "lunch",
    val use_ai: Boolean = true,
)

data class MealCreateResponse(
    val meals: List<MealDto> = emptyList(),
    val count: Int = 0,
    val message: String? = null,
    val meal: MealDto? = null,
) {
    fun registeredMeals(): List<MealDto> = when {
        meals.isNotEmpty() -> meals
        meal != null -> listOf(meal)
        else -> emptyList()
    }
}

data class ProfileUpdateRequest(
    val age: Int? = null,
    val sex: String? = null,
    val weight_kg: Double? = null,
    val height_cm: Double? = null,
    val activity_level: String? = null,
    val goal: String? = null,
    val onboarding_completed: Boolean? = true,
)

data class DietSelectRequest(val diet_plan_id: Int)

data class MenuGenerateRequest(val horizon: String)

data class NutritionistContextDto(
    val age: Int? = null,
    val weight_kg: Double? = null,
    val target_weight_kg: Double? = null,
    val height_cm: Double? = null,
    val goal: String? = null,
    val diet_name: String? = null,
    val diet_slug: String? = null,
    val calorie_target: Int? = null,
    val meals_recent_count: Int? = null,
    val likely_gaps: List<String> = emptyList(),
    val based_on_profile: Boolean? = true,
)

data class NutritionistContextResponse(
    val context: NutritionistContextDto? = null,
    val disclaimer: String? = null,
)

data class NutritionistChatMessageDto(
    val role: String,
    val content: String,
)

data class NutritionistChatRequest(
    val message: String,
    val history: List<NutritionistChatMessageDto> = emptyList(),
)

data class NutritionistChatResponse(
    val reply: String,
    val focus: List<String> = emptyList(),
    val context: NutritionistContextDto? = null,
    val disclaimer: String? = null,
)

data class MealSuggestionsRequest(
    val request: String,
    val history: List<NutritionistChatMessageDto> = emptyList(),
)

data class MealSuggestionDto(
    val id: String? = null,
    val target_date: String? = null,
    val meal_type: String? = null,
    val meal_type_label: String? = null,
    val title: String? = null,
    val description: String? = null,
    val reason: String? = null,
    val calories: Double? = null,
    val protein_g: Double? = null,
    val carbs_g: Double? = null,
    val fat_g: Double? = null,
)

data class MealSuggestionsResponse(
    val summary: String? = null,
    val nutrient_focus: List<String> = emptyList(),
    val suggestions: List<MealSuggestionDto> = emptyList(),
    val context: NutritionistContextDto? = null,
    val disclaimer: String? = null,
)
