<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * کد OTP برای ارسال
     *
     * @var string
     */
    public $code;

    /**
     * نام کاربر
     *
     * @var string
     */
    public $firstName;

    /**
     * مدت زمان اعتبار کد (به دقیقه)
     *
     * @var int
     */
    public $expiresInMinutes;

    /**
     * Create a new message instance.
     */
    public function __construct(string $code, string $firstName, int $expiresInMinutes = 5)
    {
        $this->code = $code;
        $this->firstName = $firstName;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'کد تایید ورود به حساب کاربری',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
