<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BettingPaymenSucessful extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public $transaction)
    {

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
        $senderName = $this->transaction->user->first_name ;
        $amount = number_format($this->transaction->amount, 2);

        return (new MailMessage)
            ->subject('Betting Payment Successful')
            ->greeting("Hello {$notifiable->first_name},")
            ->view('email.betting_payment_successful', [
                'amount' => $amount,
                'senderName' => $senderName,
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
        return [
            'title' => 'Betting Payment Successful',
            'message' => "Your betting payment of ₦{$amount} was successful.",
        ];
    }

}
