<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticket;
    protected $previousStatus;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(Ticket $ticket, $previousStatus)
    {
        $this->ticket = $ticket;
        $this->previousStatus = $previousStatus;
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
        $statusText = [
            'open' => 'باز',
            'closed' => 'بسته شده',
            'pending' => 'در انتظار بررسی'
        ];

        $mailMessage = (new MailMessage)
            ->subject('تغییر وضعیت تیکت #' . $this->ticket->id)
            ->line('وضعیت تیکت شما با موضوع "' . $this->ticket->subject . '" تغییر کرده است.')
            ->line('وضعیت جدید: ' . $statusText[$this->ticket->status]);

        if ($this->ticket->status === 'closed') {
            $mailMessage->line('این تیکت بسته شده است. جهت طرح درخواست جدید، لطفاً تیکت جدیدی ایجاد نمایید.');
        }
            
        return $mailMessage
            ->action('مشاهده تیکت', url('/tickets/' . $this->ticket->id))
            ->line('با تشکر از شما');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->ticket->status,
            'time' => now()->toDateTimeString(),
            'type' => 'ticket_status_changed',
        ];
    }
} 