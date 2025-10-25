<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRestrictedNotification extends Notification
{
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
        $status = $this->user->is_account_restricted ? 'restricted' : 'lifted';

        return (new MailMessage)
            ->subject('Account Restriction ' . ($status === 'restricted' ? 'Applied' : 'Lifted'))
            ->greeting("Hello {$notifiable->first_name},")
            ->markdown('email.alert_user_account_restricted', [
                'user' => $this->user,
                'status' => $status,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->user->is_account_restricted ? 'Account Restricted' : 'Restriction Lifted';
        $message = $this->user->is_account_restricted
            ? 'Your account has been temporarily restricted. Some actions may be limited until this restriction is reviewed.'
            : 'Your account restriction has been lifted. You now have full access to your account features.';

        return [
            'title' => $title,
            'message' => $message,
        ];
    }

}
