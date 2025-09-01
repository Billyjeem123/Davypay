<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NombaVirtualAccountDepositNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $transaction;
    protected $data;
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
        return ['mail','database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject('Transaction Notification')
            ->greeting("Hello {$notifiable->first_name},")
            ->view('email.nomba_virtual_deposit_successful', [
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
        $senderName = $this->data['data']['customer']['senderName'] ?? 'Someone';
        $amount = $this->data['data']['transaction']['transactionAmount'] ?? 0;
        $formattedAmount = number_format($amount, 2);

        return [
            'title' => 'Money Received',
            'message' => "You've just received ₦{$formattedAmount} in your wallet from {$senderName}.",
        ];
    }

}
