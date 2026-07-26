<?php

use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Dashboard;
use App\Livewire\DietsPage;
use App\Livewire\MealCreate;
use App\Livewire\MenusPage;
use App\Livewire\ProfileForm;
use App\Livewire\ProgressPage;
use App\Livewire\TipsPage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');
    Route::get('/progress', ProgressPage::class)->name('progress');
    Route::get('/profile', ProfileForm::class)->name('profile');
    Route::get('/diets', DietsPage::class)->name('diets');
    Route::get('/meals/create', MealCreate::class)->name('meals.create');
    Route::get('/menus', MenusPage::class)->name('menus');
    Route::get('/tips', TipsPage::class)->name('tips');
});
