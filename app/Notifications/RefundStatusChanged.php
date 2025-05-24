<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\PaymentTransaction;

class RefundStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * تراکنش پرداخت
     *
     * @var \App\Models\PaymentTransaction
     */
    protected $transaction;

    /**
     * ایجاد یک نمونه جدید از نوتیفیکیشن.
     *
     * @param  \App\Models\PaymentTransaction  $transaction
     * @return void
     */
    public function __construct(PaymentTransaction $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * تعیین کانال‌های ارسال نوتیفیکیشن.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'mail'];
    }

    /**
     * قالب پیام ایمیل برای نوتیفیکیشن.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $mailMessage = new MailMessage;
        
        if ($this->transaction->status === 'approved') {
            return $mailMessage
                ->subject('تایید درخواست استرداد وجه سفارش #' . $this->transaction->order_id)
                ->line('درخواست استرداد وجه شما برای سفارش #' . $this->transaction->order_id . ' تایید شد.')
                ->line('مبلغ: ' . number_format($this->transaction->amount) . ' ریال')
                ->line('روش استرداد: ' . $this->getRefundMethodName($this->transaction->refund_method))
                ->action('مشاهده جزئیات', url('/dashboard/orders/' . $this->transaction->order_id))
                ->line('با تشکر از همراهی شما');
        } else {
            return $mailMessage
                ->subject('رد درخواست استرداد وجه سفارش #' . $this->transaction->order_id)
                ->line('متاسفانه درخواست استرداد وجه شما برای سفارش #' . $this->transaction->order_id . ' رد شد.')
                ->line('مبلغ درخواستی: ' . number_format($this->transaction->amount) . ' ریال')
                ->action('مشاهده جزئیات', url('/dashboard/orders/' . $this->transaction->order_id))
                ->line('برای اطلاعات بیشتر با پشتیبانی تماس بگیرید');
        }
    }

    /**
     * داده‌های نوتیفیکیشن برای کانال دیتابیس.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toDatabase($notifiable)
    {
        return [
            'order_id' => $this->transaction->order_id,
            'transaction_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'status' => $this->transaction->status,
            'refund_method' => $this->transaction->refund_method,
            'title' => $this->transaction->status === 'approved' ? 
                'تایید درخواست استرداد وجه' : 
                'رد درخواست استرداد وجه',
            'content' => $this->transaction->status === 'approved' ? 
                'درخواست استرداد وجه شما به مبلغ ' . number_format($this->transaction->amount) . ' ریال تایید شد.' : 
                'درخواست استرداد وجه شما به مبلغ ' . number_format($this->transaction->amount) . ' ریال رد شد.',
            'time' => now()->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * تبدیل کد روش استرداد به نام فارسی
     *
     * @param  string  $method
     * @return string
     */
    private function getRefundMethodName($method)
    {
        switch ($method) {
            case 'wallet':
                return 'کیف پول';
            case 'gift_card':
                return 'کارت هدیه';
            case 'bank_gateway':
                return 'درگاه بانکی';
            default:
                return $method;
        }
    }
}