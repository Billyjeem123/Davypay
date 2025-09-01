<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VtPassTransactionFailed extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    protected $transaction;
    protected $status;
    public function __construct( $transaction, $status)
    {
        $this->transaction = $transaction;
        $this->status = $status;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your Bill Payment Has Failed')
            ->greeting("Hello {$notifiable->first_name},")
            ->view('email.vtpass_tranx_failed', [
                'data' => json_decode(json_encode($this->transaction)), // Converts nested arrays to objects
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $transaction = json_decode(json_encode($this->transaction));

        $productName = $transaction->content->transactions->product_name ?? 'product';

        return [
            'title' => 'Transaction Reversed',
            'message' => "We regret to inform you that your {$productName} purchase could not be completed. Your payment has been reversed.",
        ];
    }


}
