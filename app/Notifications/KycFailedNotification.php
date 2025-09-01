<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycFailedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */

    public function __construct(public User $user, public string $status, public string $rawTier, public string $reason) {
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }


    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject('Account Upgrade Notification')
            ->greeting("Hello {$notifiable->first_name},")
            ->view('email.account_upgraded_failed', [
                'user' => ($this->user),
                'tier' =>  $this->rawTier,
                'reason' =>  $this->reason,
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
            'title'   => 'Upgrade Failed',
            'message' => "Your upgrade to {$this->rawTier} failed",
        ];
    }


}
