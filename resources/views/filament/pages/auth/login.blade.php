<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>

    @livewireStyles

    <style>
        @font-face {
            font-family: 'Vazirmatn';
            src: url('https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/fonts/webfonts/Vazirmatn-Regular.woff2') format('woff2');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        body {
            font-family: 'Vazirmatn', tahoma, sans-serif;
            background-color: #ffffff;
            height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            direction: rtl;
        }

        .login-container {
            width: 100%;
            max-width: 800px;
            display: flex;
            flex-direction: row-reverse;
            align-items: center;
            justify-content: space-between;
        }

        .logo-container {
            flex: 1;
            display: flex;
            justify-content: center;
            margin-left: 2rem;
        }

        .logo {
            max-width: 300px;
            height: auto;
        }

        .form-container {
            flex: 1;
            max-width: 400px;
        }

        .form-group {
            margin-bottom: 2rem;
            text-align: right;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            font-size: 1rem;
            color: #000;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-family: 'Vazirmatn', tahoma, sans-serif;
            font-size: 0.875rem;
        }

        .login-button {
            width: 100%;
            padding: 0.75rem 1rem;
            background-color: #6366f1;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            font-family: 'Vazirmatn', tahoma, sans-serif;
            font-size: 1rem;
        }

        .text-red-500 {
            color: #ef4444;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }

        .remember-me {
            display: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="form-container">
            <form wire:submit.prevent="authenticate">
                <div class="form-group">
                    <label for="email" class="form-label">ایمیل:</label>
                    <input id="email" type="email" wire:model="data.email" required autofocus class="form-input" placeholder="example@gmail.com" />
                    @error('data.email') <span class="text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">رمز:</label>
                    <input id="password" type="password" wire:model="data.password" required class="form-input" placeholder="example@gmail.com" />
                    @error('data.password') <span class="text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="remember-me">
                    <input id="remember" type="checkbox" wire:model="data.remember" />
                    <label for="remember">مرا به خاطر بسپار</label>
                </div>

                <button type="submit" class="login-button">ورود</button>
            </form>
        </div>

        <div class="logo-container">
            <img
                class="logo"
                src="{{ asset('images/logo.png') }}"
                alt="کانکت"
                onerror="this.src='data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIzMDAiIGhlaWdodD0iMjAwIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2ZmZmZmZiI+PC9yZWN0Pjxwb2x5Z29uIHBvaW50cz0iMTUwLDUwIDUwLDE1MCAyNTAsMTUwIiBmaWxsPSIjMjU2M2ViIj48L3BvbHlnb24+PHRleHQgeD0iMTUwIiB5PSIxMjAiIGZvbnQtc2l6ZT0iMzAiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGFsaWdubWVudC1iYXNlbGluZT0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZpbGw9IiMyNTYzZWIiPtio2Kcg2qnYp9mG2qnYqjwvdGV4dD48L3N2Zz4=';"
            />
        </div>
    </div>

    @livewireScripts
</body>
</html>
