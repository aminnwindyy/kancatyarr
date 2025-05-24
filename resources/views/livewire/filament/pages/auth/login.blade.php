<form wire:submit.prevent="authenticate" class="login-form">
    <div class="form-group">
        <label for="email" class="form-label">ایمیل:</label>
        <input id="email" type="email" wire:model="data.email" required autofocus class="form-input" />
        @error('data.email') <span class="text-red-500">{{ $message }}</span> @enderror
    </div>

    <div class="form-group">
        <label for="password" class="form-label">رمز:</label>
        <input id="password" type="password" wire:model="data.password" required class="form-input" />
        @error('data.password') <span class="text-red-500">{{ $message }}</span> @enderror
    </div>

    <div class="remember-me">
        <input id="remember" type="checkbox" wire:model="data.remember" />
        <label for="remember">مرا به خاطر بسپار</label>
    </div>

    <button type="submit" class="login-button">ورود</button>
</form>
