<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountBannedNotification extends Notification
{
    use Queueable;

    use Queueable;

    public $user;

    /**
     * Create a new notification instance.
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
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
        $status = $this->user->is_ban ? 'deactivated' : 'reactivated';

        return (new MailMessage)
            ->subject('Account ' . ucfirst($status))
            ->greeting("Hello {$notifiable->first_name},")
            ->markdown('email.alert_user_account_status', [
                'user' => $this->user,
                'status' => $status,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->user->is_ban ? 'Account Banned' : 'Ban Lifted';
        $message = $this->user->is_ban
            ? 'Your account has been banned due to policy violation or suspicious activity.'
            : 'Your account ban has been lifted. You can now access your account normally.';

        return [
            'title' => $title,
            'message' => $message,
        ];
    }

}
