<?php

namespace App\Notifications;

use App\Models\ServiceProvider;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ServiceProviderMessage extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The service provider instance.
     *
     * @var \App\Models\ServiceProvider
     */
    protected $serviceProvider;

    /**
     * The admin user sending the message.
     *
     * @var \App\Models\User
     */
    protected $admin;

    /**
     * The message subject.
     *
     * @var string
     */
    protected $subject;

    /**
     * The message content.
     *
     * @var string
     */
    protected $content;

    /**
     * Create a new notification instance.
     */
    public function __construct(ServiceProvider $serviceProvider, User $admin, string $subject, string $content)
    {
        $this->serviceProvider = $serviceProvider;
        $this->admin = $admin;
        $this->subject = $subject;
        $this->content = $content;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->greeting('سلام ' . $this->serviceProvider->name)
            ->line($this->content)
            ->line('این ایمیل توسط ادمین پنل برای شما ارسال شده است.')
            ->line('با تشکر')
            ->salutation('تیم پشتیبانی');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'service_provider_id' => $this->serviceProvider->id,
            'admin_id' => $this->admin->user_id,
            'admin_name' => $this->admin->first_name . ' ' . $this->admin->last_name,
            'subject' => $this->subject,
            'content' => $this->content,
        ];
    }
}
