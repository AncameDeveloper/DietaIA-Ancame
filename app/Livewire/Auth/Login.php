<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Entrar — DietaIA')]
class Login extends Component
{
    public string $email = '';
    public string $password = '';

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $existing = User::query()->where('email', $credentials['email'])->first();
        if ($existing && blank($existing->password)) {
            $this->addError('email', 'Esta cuenta usa Google. Continúa con Google.');

            return;
        }

        if (! Auth::attempt($credentials, true)) {
            $this->addError('email', 'Credenciales incorrectas.');

            return;
        }

        session()->regenerate();
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
