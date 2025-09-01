<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class NombaFundedWallet extends Notification
{
    use Queueable;

    public  $transaction;
    public  $data;

    /**
     *
     */

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
        $amount =  ($this->data['data']['order']['amount']);
        return (new MailMessage)
            ->subject('Transaction Notification')
            ->greeting("Hello {$notifiable->first_name},")
            ->view('email.nomba_wallet_funded', [
                'amount' => $amount,
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
        $amount =  ($this->data['data']['order']['amount']);
        return [
            'title' => "Transaction Notification",
            'message' => 'Great news! Your wallet has been funded with ₦' . number_format($amount, 2) . ' successfully',
        ];
    }
}
