# DietaIA

Aplicación web + Android nativa para seguimiento de dietas y nutrición con IA (Gemini).

> Aviso: DietaIA ofrece orientación general y **no sustituye** consejo médico ni nutricional profesional.

## Stack

- **Backend + Web:** Laravel 13, Livewire, MySQL, Sanctum
- **Android:** Kotlin, Jetpack Compose, Retrofit, DataStore
- **IA:** Google Gemini (`GEMINI_API_KEY`) — si no hay clave, usa estimaciones locales de respaldo

## Requisitos

- Laragon (PHP 8.3+, MySQL, Composer)
- Android Studio (Ladybug+) para la app nativa

## Setup backend / web

```bash
cd c:\laragon\www\DietaIA
composer install
copy .env.example .env   # si hace falta
php artisan key:generate
# Crear BD `dietaia` en MySQL (ya prevista en .env)
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Web: http://127.0.0.1:8000  
Demo: `demo@dietaia.test` / `password`

Opcional IA real:

```
GEMINI_API_KEY=tu_clave
GEMINI_MODEL=gemini-flash-latest
```

## API (Sanctum)

Base: `http://127.0.0.1:8000/api`

| Método | Ruta | Descripción |
|--------|------|-------------|
| POST | /register, /login, /logout | Auth |
| GET/PUT | /profile | Perfil y objetivos |
| GET/POST | /diet-plans, /select, /suggest | Dietas |
| GET/POST | /meals, /meals/analyze-photo | Comidas texto/foto |
| GET | /dashboard/today | Resumen del día |
| POST/GET | /menus/generate, /menus/latest | Menús |
| GET | /tips | Consejos |

## Android

1. Abrir carpeta `android/` en Android Studio
2. Sync Gradle
3. Emulador: la API apunta a `http://10.0.2.2:8000/api/` (host)
4. Mantener `php artisan serve` en marcha
5. Run en emulador/dispositivo

Para dispositivo físico, cambia `API_BASE_URL` en `android/app/build.gradle.kts` a la IP de tu PC en la LAN.

## Funciones MVP

- Perfil + cálculo TDEE / calorías objetivo
- Planes: keto, ayuno intermitente, déficit, mediterránea, alta proteína
- Registro de comidas por texto o foto (IA)
- Dashboard de macros y micronutrientes
- Menús diario/semanal y consejos
