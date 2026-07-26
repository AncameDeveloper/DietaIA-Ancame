<div class="auth-wrap">
    <h1>DietaIA</h1>
    <p class="muted">Accede a tu seguimiento nutricional.</p>
    <div class="card">
        <form wire:submit="login">
            <label>Email</label>
            <input type="email" wire:model="email">
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label>Contraseña</label>
            <input type="password" wire:model="password">
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <button class="btn btn-primary" type="submit">Entrar</button>
        </form>
        <p class="muted" style="margin-top:1rem">¿Nuevo? <a href="{{ route('register') }}">Crear cuenta</a></p>
        <p class="muted">Demo: demo@dietaia.test / password</p>
    </div>
</div>
