<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use App\Services\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Profile::create(['user_id' => $user->id]);

        $token = $user->createToken('android')->plainTextToken;

        return response()->json([
            'user' => $user->load('profile'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $existing = User::query()->where('email', $credentials['email'])->first();
        if ($existing && blank($existing->password)) {
            throw ValidationException::withMessages([
                'email' => ['Esta cuenta usa Google. Continúa con Google.'],
            ]);
        }

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        /** @var User $user */
        $user = Auth::user();
        $token = $user->createToken('android')->plainTextToken;

        return response()->json([
            'user' => $user->load(['profile', 'activeDietAssignment.dietPlan']),
            'token' => $token,
        ]);
    }

    public function google(Request $request, GoogleAuthService $googleAuth): JsonResponse
    {
        $data = $request->validate([
            'id_token' => ['required', 'string'],
        ]);

        try {
            $payload = $googleAuth->verifyIdToken($data['id_token']);
            $user = $googleAuth->findOrCreateFromGoogle(
                googleId: $payload['sub'],
                email: $payload['email'],
                name: $payload['name'],
                avatar: $payload['picture'],
                emailVerified: (bool) ($payload['email_verified'] ?? false),
            );
            $token = $googleAuth->issueApiToken($user);

            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages([
                'id_token' => [$e->getMessage()],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo autenticar con Google.',
            ], 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(
            $request->user()->load(['profile', 'activeDietAssignment.dietPlan'])
        );
    }
}
