<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ServiceProvider;

class NewServiceProviderRegistered extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * خدمات دهنده‌ای که ثبت نام کرده است
     *
     * @var \App\Models\ServiceProvider
     */
    protected $serviceProvider;

    /**
     * ایجاد نمونه جدید از نوتیفیکیشن.
     */
    public function __construct(ServiceProvider $serviceProvider)
    {
        $this->serviceProvider = $serviceProvider;
    }

    /**
     * دریافت کانال‌های ارسال نوتیفیکیشن.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * ساخت پیام ایمیل نوتیفیکیشن.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('ثبت نام خدمات دهنده جدید')
            ->greeting('سلام!')
            ->line('یک خدمات دهنده جدید در سیستم ثبت نام کرده است.')
            ->line('نام: ' . $this->serviceProvider->name)
            ->line('ایمیل: ' . $this->serviceProvider->email)
            ->line('تلفن: ' . $this->serviceProvider->phone)
            ->line('دسته: ' . ($this->serviceProvider->category === 'commercial' ? 'تجاری' : 'کانکت یار'))
            ->action('مشاهده مدارک', url('/admin/service-providers/' . $this->serviceProvider->id))
            ->line('لطفا مدارک را بررسی کنید و در صورت تایید، وضعیت خدمات دهنده را به "تایید شده" تغییر دهید.');
    }

    /**
     * ساخت محتوای نوتیفیکیشن برای ذخیره در دیتابیس.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'id' => $this->serviceProvider->id,
            'title' => 'ثبت نام خدمات دهنده جدید',
            'message' => 'یک خدمات دهنده جدید به نام ' . $this->serviceProvider->name . ' ثبت نام کرده است.',
            'type' => 'service_provider_registration',
            'url' => '/admin/service-providers/' . $this->serviceProvider->id,
        ];
    }

    /**
     * دریافت آرایه مربوط به نوتیفیکیشن برای ارسال در Slack.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'service_provider_id' => $this->serviceProvider->id,
            'name' => $this->serviceProvider->name,
            'email' => $this->serviceProvider->email,
            'phone' => $this->serviceProvider->phone,
            'category' => $this->serviceProvider->category,
        ];
    }
}
