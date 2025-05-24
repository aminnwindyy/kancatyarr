<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTestUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:test-user {email?} {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test user for API testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email') ?? 'test@example.com';
        $password = $this->argument('password') ?? 'password';

        // حذف کاربر قبلی با ایمیل یکسان اگر وجود داشت
        User::where('email', $email)->delete();

        // ایجاد کاربر جدید
        $user = User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => Hash::make($password),
            'phone_number' => '09123456789',
            'is_active' => true,
            'is_admin' => false,
            'email_verified_at' => now(),
            'national_id' => '1234567890',
            'sheba_number' => 'IR12345678901234567890',
        ]);

        // ایجاد توکن
        $token = $user->createToken('test-token')->plainTextToken;

        $this->info('کاربر تست با موفقیت ایجاد شد!');
        $this->info('User ID: ' . $user->user_id);
        $this->info('Email: ' . $email);
        $this->info('Password: ' . $password);
        $this->info('Token: ' . $token);

        return 0;
    }
}
