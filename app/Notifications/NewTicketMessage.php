<?php

namespace App\Notifications;

use App\Models\TicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTicketMessage extends Notification implements ShouldQueue
{
    use Queueable;

    protected $ticketMessage;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(TicketMessage $ticketMessage)
    {
        $this->ticketMessage = $ticketMessage;
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
        $ticket = $this->ticketMessage->ticket;
        $sender = $this->ticketMessage->sender;
        $isAdmin = $sender->hasRole('admin') || $sender->hasRole('support');
        
        return (new MailMessage)
            ->subject('پیام جدید در تیکت #' . $ticket->id . ': ' . $ticket->subject)
            ->line($isAdmin ? 'پشتیبان به تیکت شما پاسخ داده است:' : 'پیام جدیدی در تیکت شما ارسال شده است:')
            ->line('"' . substr($this->ticketMessage->content, 0, 100) . (strlen($this->ticketMessage->content) > 100 ? '...' : '') . '"')
            ->action('مشاهده تیکت', url('/tickets/' . $ticket->id))
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
        $ticket = $this->ticketMessage->ticket;
        $sender = $this->ticketMessage->sender;
        
        return [
            'ticket_id' => $ticket->id,
            'message_id' => $this->ticketMessage->id,
            'sender_id' => $sender->id,
            'sender_name' => $sender->name,
            'subject' => $ticket->subject,
            'content' => substr($this->ticketMessage->content, 0, 100) . (strlen($this->ticketMessage->content) > 100 ? '...' : ''),
            'time' => now()->toDateTimeString(),
            'type' => 'new_ticket_message',
        ];
    }
} 