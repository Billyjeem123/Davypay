<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InAppTransferReceivedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    protected $sender;
    protected $amount;
    protected $reference;

    public function __construct(User $sender, float $amount, string $reference)
    {
        $this->sender = $sender;
        $this->amount = $amount;
        $this->reference = $reference;
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
        $senderName = $this->sender->first_name . ' ' . $this->sender->last_name;

        return (new MailMessage)
            ->subject('Transaction Notification')
            ->greeting("Hello {$notifiable->first_name},")
            ->view('email.in_app_transfer_received', [
                'user' => $notifiable,
                'amount' => $this->amount,
                'senderName' => $senderName,
                'reference' => $this->reference,
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
            'title' => 'Money Received',
            'message' => "You received ₦" . number_format($this->amount, 2) . " from " . $this->sender->first_name . " " . $this->sender->last_name,
        ];
    }
}
