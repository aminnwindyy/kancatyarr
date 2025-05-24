<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Login extends Component
{
    use WithRateLimiting;

    public ?array $data = [
        'email' => '',
        'password' => '',
        'remember' => false,
    ];

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getHomeUrl());
        }
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('Too many login attempts'))
                ->body(__('Please wait :seconds seconds before trying again.', [
                    'seconds' => $exception->secondsUntilAvailable,
                ]))
                ->danger()
                ->send();

            return null;
        }

        $data = $this->data;

        if (! Filament::auth()->attempt([
            'email' => $data['email'],
            'password' => $data['password'],
        ], $data['remember'])) {
            throw ValidationException::withMessages([
                'data.email' => __('These credentials do not match our records.'),
            ]);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    public function render(): View
    {
        return view('filament.pages.auth.login');
    }
}
