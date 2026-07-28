import java.io.File

plugins {
    id("com.android.application") version "8.7.3" apply false
    id("org.jetbrains.kotlin.android") version "2.0.21" apply false
    id("org.jetbrains.kotlin.plugin.compose") version "2.0.21" apply false
}

// Evita AccessDeniedException en Windows/Laragon: build fuera de c:\laragon\www
val externalBuildRoot = File(
    System.getenv("LOCALAPPDATA") ?: System.getProperty("user.home"),
    "DietaIA-android-build",
)

layout.buildDirectory.set(externalBuildRoot.resolve("root"))

subprojects {
    val projectName = name
    layout.buildDirectory.set(externalBuildRoot.resolve(projectName))
}
