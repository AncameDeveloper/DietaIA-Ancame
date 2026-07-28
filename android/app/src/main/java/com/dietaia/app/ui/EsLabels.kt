package com.dietaia.app.ui

/**
 * Etiquetas en español para valores de API (códigos en inglés).
 */
object EsLabels {
    fun mealType(type: String?): String = when (type?.lowercase()?.trim()) {
        "breakfast" -> "Desayuno"
        "lunch" -> "Comida"
        "dinner" -> "Cena"
        "snack" -> "Snack"
        else -> type?.replaceFirstChar { it.uppercase() } ?: "Comida"
    }

    fun mealTypeEmoji(type: String?): String = when (type?.lowercase()?.trim()) {
        "breakfast" -> "🍳"
        "lunch" -> "🥗"
        "dinner" -> "🐟"
        "snack" -> "🍎"
        else -> "🍽️"
    }

    fun horizon(value: String?): String = when (value?.lowercase()?.trim()) {
        "weekly", "week", "semanal" -> "Semanal"
        "daily", "day", "diario" -> "Diario"
        else -> value ?: ""
    }

    fun mealSource(source: String?): String = when (source?.lowercase()?.trim()) {
        "text_ai", "photo_ai" -> "Añadido por IA"
        "menu" -> "Desde menú"
        "manual" -> "Manual"
        else -> "Registrado"
    }

    /** Opciones de tipo de comida: valor API → etiqueta visible. */
    val mealTypeOptions: List<Pair<String, String>> = listOf(
        "breakfast" to "Desayuno",
        "lunch" to "Comida",
        "dinner" to "Cena",
        "snack" to "Snack",
    )
}
