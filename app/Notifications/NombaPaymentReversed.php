<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NombaPaymentReversed extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public  $transaction;
    public  $data;

    /**

    /**
     * Create a new notification instance.
     */
    public function __construct($transaction, $data)
    {
        $this->transaction = $transaction;
        $this->data = $data;

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
            ->subject('Transaction Notification')
            ->greeting("Hello {$notifiable->first_name},")
            ->view('email.nomba_payment_reversed', [
                'data' => ($this->data),
                'transaction' => $this->transaction,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return
            [
                'title' => 'Payment Reversed',
                'message' => 'Your payment of ₦' . number_format($this->transaction->amount, 2)   .  ' has been reversed. Amount refunded to your wallet.',
                "data" => [
                    'type' => 'payment_reversed',
                    'amount' => $this->transaction->amount,
                    'status' => 'reversed',
                    'timestamp' => now()->toISOString(),
                ]
            ];
    }
}
