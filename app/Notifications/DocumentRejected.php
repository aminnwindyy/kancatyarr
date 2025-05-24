<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\ServiceProviderDocument;

class DocumentRejected extends Notification implements ShouldQueue
{
    use Queueable;

    protected $document;

    /**
     * Create a new notification instance.
     *
     * @param ServiceProviderDocument $document
     * @return void
     */
    public function __construct(ServiceProviderDocument $document)
    {
        $this->document = $document;
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
        $documentType = [
            'national_card' => 'کارت ملی',
            'business_license' => 'جواز کسب',
            'photo' => 'عکس پرسنلی',
        ][$this->document->document_type] ?? $this->document->document_type;

        return (new MailMessage)
            ->subject('بروزرسانی وضعیت مدارک شما')
            ->greeting('سلام ' . $notifiable->name)
            ->line('مدرک شما (' . $documentType . ') نیاز به بروزرسانی دارد.')
            ->line('توضیحات: ' . ($this->document->description ?: 'بدون توضیحات'))
            ->line('لطفاً وارد پنل کاربری خود شده و مدارک خود را بروزرسانی نمایید.')
            ->action('ورود به پنل کاربری', url('/login'))
            ->line('از همکاری شما سپاسگزاریم.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $documentType = [
            'national_card' => 'کارت ملی',
            'business_license' => 'جواز کسب',
            'photo' => 'عکس پرسنلی',
        ][$this->document->document_type] ?? $this->document->document_type;

        return [
            'document_id' => $this->document->id,
            'document_type' => $this->document->document_type,
            'document_type_fa' => $documentType,
            'status' => 'rejected',
            'description' => $this->document->description,
        ];
    }
}
