<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DietPlanController;
use App\Http\Controllers\Api\MealController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MicronutrientController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ProgressController;
use App\Http\Controllers\Api\TipController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/auth/google', [AuthController::class, 'google'])->middleware('throttle:20,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);

    Route::get('/diet-plans', [DietPlanController::class, 'index']);
    Route::post('/diet-plans/select', [DietPlanController::class, 'select']);
    Route::post('/diet-plans/suggest', [DietPlanController::class, 'suggest'])->middleware('throttle:10,1');

    Route::get('/meals', [MealController::class, 'index']);
    Route::post('/meals', [MealController::class, 'store'])->middleware('throttle:30,1');
    Route::post('/meals/analyze-photo', [MealController::class, 'analyzePhoto'])->middleware('throttle:10,1');
    Route::post('/meals/{meal}/confirm', [MealController::class, 'confirm']);
    Route::delete('/meals/{meal}', [MealController::class, 'destroy']);

    Route::get('/dashboard/today', [DashboardController::class, 'today']);
    Route::get('/dashboard/micronutrients', [MicronutrientController::class, 'index']);
    Route::get('/progress/weight', [ProgressController::class, 'weight']);

    Route::get('/menus', [MenuController::class, 'index']);
    Route::get('/menus/latest', [MenuController::class, 'latest']);
    Route::post('/menus/generate', [MenuController::class, 'generate'])->middleware('throttle:10,1');
    Route::post('/menus/shopping-list', [MenuController::class, 'shoppingList'])->middleware('throttle:30,1');
    // Alias solicitado: /api/menu/shopping-list
    Route::post('/menu/shopping-list', [MenuController::class, 'shoppingList'])->middleware('throttle:30,1');

    Route::get('/tips', [TipController::class, 'index'])->middleware('throttle:20,1');
});
