package com.dietaia.app.ui.theme

import androidx.compose.material3.MaterialTheme
import androidx.compose.material3.lightColorScheme
import androidx.compose.runtime.Composable
import androidx.compose.ui.graphics.Color

private val Green = Color(0xFF2F6F4E)
private val GreenDark = Color(0xFF1C2B24)
private val Cream = Color(0xFFF3F6F2)

private val LightColors = lightColorScheme(
    primary = Green,
    onPrimary = Color.White,
    secondary = Color(0xFF5C6F66),
    background = Cream,
    surface = Color.White,
    onBackground = GreenDark,
    onSurface = GreenDark,
)

@Composable
fun DietaIATheme(content: @Composable () -> Unit) {
    MaterialTheme(
        colorScheme = LightColors,
        content = content,
    )
}
