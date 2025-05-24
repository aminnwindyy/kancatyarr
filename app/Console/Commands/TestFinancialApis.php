<?php

namespace App\Console\Commands;

use App\Models\BankCard;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class TestFinancialApis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:financial-apis';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'تست API های بخش مالی';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('شروع تست API های مالی...');

        // یافتن یا ایجاد کاربر تست
        $user = User::where('email', 'test@example.com')->first();
        if (!$user) {
            $this->error('کاربر تست یافت نشد! لطفا ابتدا با دستور create:test-user یک کاربر تست ایجاد کنید.');
            return 1;
        }

        // احراز هویت کاربر
        Auth::login($user);
        $this->info('کاربر با موفقیت احراز هویت شد.');

        // تست 1: کیف پول
        $this->info('تست 1: بررسی کیف پول...');
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->user_id],
            ['balance' => 0, 'gift_balance' => 0]
        );
        $this->info("کیف پول کاربر: موجودی عادی: {$wallet->balance} - موجودی هدیه: {$wallet->gift_balance}");

        // تست 2: افزودن کارت بانکی
        $this->info('تست 2: افزودن کارت بانکی...');
        $bankCard = new BankCard([
            'user_id' => $user->user_id,
            'card_number' => '6037991012345678',
            'sheba_number' => 'IR123456789012345678',
            'bank_name' => 'بانک ملی ایران',
            'expiry_date' => '2026-12-01',
            'cvv' => '123',
            'is_active' => true,
        ]);
        $bankCard->save();
        $this->info("کارت بانکی با موفقیت اضافه شد. شناسه کارت: {$bankCard->card_id}");

        // تست 3: دریافت لیست کارت‌های بانکی
        $this->info('تست 3: دریافت لیست کارت‌های بانکی...');
        $bankCards = BankCard::where('user_id', $user->user_id)->get();
        $this->info("تعداد کارت‌های بانکی: " . count($bankCards));
        foreach ($bankCards as $card) {
            $this->info("کارت شماره {$card->card_id}: {$card->card_number} - {$card->bank_name}");
        }

        // تست 4: حذف کارت بانکی
        $this->info('تست 4: حذف کارت بانکی...');
        $cardToDelete = BankCard::where('user_id', $user->user_id)->first();
        if ($cardToDelete) {
            $cardId = $cardToDelete->card_id;
            $cardToDelete->delete();
            $this->info("کارت بانکی با شناسه {$cardId} با موفقیت حذف شد.");
        } else {
            $this->error('هیچ کارت بانکی برای حذف یافت نشد.');
        }

        $this->info('تست API های مالی به پایان رسید.');

        return 0;
    }
}

