<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InAppTransferSentNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $recipient;
    protected $amount;
    protected $reference;
    protected $newBalance;

    public function __construct(User $recipient, float $amount, string $reference, float $newBalance)
    {
        $this->recipient = $recipient;
        $this->amount = $amount;
        $this->reference = $reference;
        $this->newBalance = $newBalance;
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



    public function toMail(object $notifiable): MailMessage
    {
        $recipientName = $this->recipient->first_name . ' ' . $this->recipient->last_name;

        return (new MailMessage)
            ->subject('Transaction Notification')
            ->greeting("Hello {$notifiable->first_name},")
            ->view('email.in_app_transfer_sent', [
                'user' => $notifiable,
                'amount' => $this->amount,
                'recipientName' => $recipientName,
                'reference' => $this->reference,
                'newBalance' => $this->newBalance,
            ]);
    }




    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title'=> 'Transaction Notification',
            'message' => "You transferred ₦" . number_format($this->amount, 2) . " to " . $this->recipient->first_name,
        ];
    }
}
