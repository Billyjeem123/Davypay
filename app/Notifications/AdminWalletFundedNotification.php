<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminWalletFundedNotification extends Notification
{
    use Queueable;

    protected $user;
    protected $amount;
    protected $transactionType;
    protected $reference;
    public $description;

    /**
     * Create a new notification instance.
     */
    public function __construct($user, $amount, $transactionType, $reference, $description)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->transactionType = $transactionType;
        $this->reference = $reference;
        $this->description = $description;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->transactionType === 'credit'
            ? 'Wallet Funded Successfully By Admin'
            : 'Wallet Debited by Admin';

        return (new MailMessage)
            ->subject($title)
            ->greeting("Hello {$this->user->name},")
            ->markdown('email.alert_user_wallet_transaction', [
                'user' => $this->user,
                'amount' => $this->amount,
                'reference' => $this->reference,
                'transactionType' => $this->transactionType,
                'walletBalance' => $this->user->wallet->amount,
                'description' => $this->description,
            ]);
    }


    /**
     * Get the array representation of the notification (for database storage).
     */
    public function toArray(object $notifiable): array
    {
        $isCredit = $this->transactionType === 'credit';

        $title = $isCredit
            ? 'Wallet Funded by Admin'
            : 'Wallet Debited by Admin';

        $message = $isCredit
            ? "Your wallet has been credited with ₦" . number_format($this->amount, 2) . " by an administrator."
            : "Your wallet has been debited by ₦" . number_format($this->amount, 2) . " by an administrator.";

        return [
            'title' => $title,
            'message' => $message,
        ];
    }

}
