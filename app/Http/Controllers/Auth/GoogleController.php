<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\GoogleAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (blank(config('services.google.client_id')) || blank(config('services.google.client_secret'))) {
            return redirect()
                ->route('login')
                ->with('error', 'Google OAuth no está configurado. Revisa GOOGLE_CLIENT_ID y GOOGLE_CLIENT_SECRET en el .env.');
        }

        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(GoogleAuthService $googleAuth): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $user = $googleAuth->findOrCreateFromSocialite($googleUser);
            Auth::login($user, true);
            request()->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        } catch (Throwable $e) {
            report($e);

            return redirect()
                ->route('login')
                ->with('error', 'No se pudo iniciar sesión con Google. Inténtalo de nuevo.');
        }
    }
}
