<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

class GoogleAuthService
{
    /**
     * @return array{sub:string,email:string,email_verified:?bool,name:?string,picture:?string}
     */
    public function verifyIdToken(string $idToken): array
    {
        $response = Http::timeout(15)->get('https://oauth2.googleapis.com/tokeninfo', [
            'id_token' => $idToken,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('El token de Google no es válido.');
        }

        $payload = $response->json();
        $aud = $payload['aud'] ?? null;
        $allowed = array_values(array_filter([
            config('services.google.client_id'),
            config('services.google.android_client_id'),
        ]));

        if (! $aud || (! empty($allowed) && ! in_array($aud, $allowed, true))) {
            throw new RuntimeException('El token de Google no corresponde a esta aplicación.');
        }

        if (empty($payload['sub']) || empty($payload['email'])) {
            throw new RuntimeException('El token de Google no incluye email o identificador.');
        }

        return [
            'sub' => (string) $payload['sub'],
            'email' => (string) $payload['email'],
            'email_verified' => filter_var($payload['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'name' => $payload['name'] ?? null,
            'picture' => $payload['picture'] ?? null,
        ];
    }

    public function findOrCreateFromGoogle(
        string $googleId,
        string $email,
        ?string $name = null,
        ?string $avatar = null,
        bool $emailVerified = false,
    ): User {
        $user = User::query()->where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::query()->where('email', $email)->first();
        }

        if ($user) {
            $user->fill([
                'google_id' => $user->google_id ?: $googleId,
                'avatar' => $avatar ?: $user->avatar,
                'name' => $user->name ?: ($name ?: Str::before($email, '@')),
                'email_verified_at' => $user->email_verified_at ?? ($emailVerified ? now() : null),
            ]);
            $user->save();
        } else {
            $user = User::create([
                'name' => $name ?: Str::before($email, '@'),
                'email' => $email,
                'google_id' => $googleId,
                'avatar' => $avatar,
                'password' => null,
                'email_verified_at' => $emailVerified ? now() : null,
            ]);
        }

        Profile::query()->firstOrCreate(['user_id' => $user->id]);

        return $user->fresh()->load(['profile', 'activeDietAssignment.dietPlan']);
    }

    public function findOrCreateFromSocialite(SocialiteUser $googleUser): User
    {
        return $this->findOrCreateFromGoogle(
            googleId: (string) $googleUser->getId(),
            email: (string) $googleUser->getEmail(),
            name: $googleUser->getName(),
            avatar: $googleUser->getAvatar(),
            emailVerified: true,
        );
    }

    public function issueApiToken(User $user, string $device = 'android-google'): string
    {
        return $user->createToken($device)->plainTextToken;
    }
}
