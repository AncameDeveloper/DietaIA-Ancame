<div class="auth-wrap">
    <h1>DietaIA</h1>
    <p class="muted">Crea tu cuenta para empezar el seguimiento.</p>
    <div class="card">
        <form wire:submit="register">
            <label>Nombre</label>
            <input type="text" wire:model="name">
            @error('name') <div class="error">{{ $message }}</div> @enderror

            <label>Email</label>
            <input type="email" wire:model="email">
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label>Contraseña</label>
            <input type="password" wire:model="password">
            @error('password') <div class="error">{{ $message }}</div> @enderror

            <label>Confirmar contraseña</label>
            <input type="password" wire:model="password_confirmation">

            <button class="btn btn-primary" type="submit">Crear cuenta</button>
        </form>
        <p class="muted" style="margin-top:1rem">¿Ya tienes cuenta? <a href="{{ route('login') }}">Entrar</a></p>
    </div>
</div>
