<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewProductAdded extends Notification implements ShouldQueue
{
    use Queueable;

    protected $product;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $serviceProvider = $this->product->serviceProvider;

        return (new MailMessage)
            ->subject('محصول جدیدی برای تایید ثبت شده است')
            ->line('محصول جدیدی توسط خدمات‌دهنده ثبت شده و در انتظار تایید می‌باشد.')
            ->line('نام محصول: ' . $this->product->name)
            ->line('خدمات‌دهنده: ' . $serviceProvider->name)
            ->line('قیمت محصول: ' . number_format($this->product->price) . ' تومان')
            ->action('مشاهده محصول', url('/admin/products/' . $this->product->product_id))
            ->line('با تشکر از همکاری شما');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $serviceProvider = $this->product->serviceProvider;
        
        return [
            'product_id' => $this->product->product_id,
            'product_name' => $this->product->name,
            'service_provider_id' => $serviceProvider->id,
            'service_provider_name' => $serviceProvider->name,
            'price' => $this->product->price,
            'time' => now()->toDateTimeString(),
            'type' => 'new_product_added',
        ];
    }
}
