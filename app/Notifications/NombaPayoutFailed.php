<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NombaPayoutFailed extends Notification
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
            ->view('email.nomba_payout_failed', [
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
        $amount = number_format($this->transaction->amount, 2);
        $recipient = $this->$data['data']['customer']['recipientName'] ?? 'recipient';

        return [
            'type' => 'payout_failed',
            'title' => 'Transfer Failed',
            'message' => "Your transfer of ₦{$amount} to {$recipient} failed. Amount refunded to wallet.",
            'data' => [
                'transaction_reference' => $this->transaction->transaction_reference,
                'amount' => $this->transaction->amount,
                'refunded' => true,
          ],
        ];
    }
}
