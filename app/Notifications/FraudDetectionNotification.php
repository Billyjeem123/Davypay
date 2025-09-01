<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FraudDetectionNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public $user;
    public $fraudResult;
    public $fraudCheckId;
    public $action;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, array $fraudResult, string $fraudCheckId, string $action)
    {
        $this->user = $user;
        $this->fraudResult = $fraudResult;
        $this->fraudCheckId = $fraudCheckId;
        $this->action = $action;
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
        $severity = $this->getFraudSeverity($this->action);
        $actionText = ucwords(str_replace('_', ' ', $this->action));

        return (new MailMessage)
            ->subject('🚨 Fraud Alert: ' . $actionText . ' - ' . $severity . ' Risk')
            ->greeting("Hello {$notifiable->first_name},")
            ->view('email.fraud_alert_notification', [
                'user' => $this->user,
                'fraudResult' => $this->fraudResult,
                'fraudCheckId' => $this->fraudCheckId,
                'severity' => $severity,
                'actionText' => $actionText,
            ]);
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'fraud_alert',
            'title' => $this->getNotificationTitle(),
            'message' => $this->getNotificationMessage(),
            'severity' => $this->getFraudSeverity($this->action),
            'user_data' => [
                'id' => $this->user->id,
                'name' => $this->user->first_name,
                'email' => $this->user->email,
                'phone' => $this->user->phone ?? null,
            ],
            'fraud_data' => [
                'check_id' => $this->fraudCheckId,
                'action_taken' => $this->action,
                'risk_factors' => $this->fraudResult['risk_factors'],
                'fraud_score' => $this->fraudResult['fraud_score'] ?? null,
                'detection_time' => now()->toISOString(),
            ],
            'icon' => 'fraud-alert',
            'created_at' => now(),
        ];
    }

    /**
     * Get notification title
     */
    private function getNotificationTitle(): string
    {
        return match ($this->action) {
            'ban_account' => '🚨 Account Banned - High Risk Fraud',
            'restrict_account' => '⚠️ Account Restricted - Medium Risk Fraud',
            'block_transaction' => '🛑 Transaction Blocked - Low Risk Fraud',
            default => '⚠️ Fraud Detection Alert'
        };
    }

    /**
     * Get notification message
     */
    private function getNotificationMessage(): string
    {
        return match ($this->action) {
            'ban_account' => "User {$this->user->name} ({$this->user->email}) has been banned due to high-risk fraud detection.",
            'restrict_account' => "User {$this->user->name} ({$this->user->email}) has been restricted due to medium-risk fraud detection.",
            'block_transaction' => "Transaction blocked for user {$this->user->name} ({$this->user->email}) due to fraud detection.",
            default => "Fraud detected for user {$this->user->name} ({$this->user->email})."
        };
    }

    /**
     * Get email message (more detailed)
     */
    private function getEmailMessage(): string
    {
        $baseMessage = $this->getNotificationMessage();
        return $baseMessage . " Our fraud detection system has automatically taken action to protect your platform.";
    }

    /**
     * Get fraud severity level
     */
    private function getFraudSeverity(string $action): string
    {
        return match ($action) {
            'ban_account' => 'HIGH',
            'restrict_account' => 'MEDIUM',
            'block_transaction' => 'LOW',
            default => 'UNKNOWN'
        };
    }

    /**
     * Format risk factors for display
     */
    private function formatRiskFactors(): string
    {
        if (is_array($this->fraudResult['risk_factors'])) {
            return '• ' . implode(PHP_EOL . '• ', $this->fraudResult['risk_factors']);
        }
        return $this->fraudResult['risk_factors'];
    }
}
