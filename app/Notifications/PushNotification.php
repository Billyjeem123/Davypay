<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\AndroidConfig;
use NotificationChannels\Fcm\Resources\AndroidNotification;
use NotificationChannels\Fcm\Resources\AndroidMessagePriority;
use NotificationChannels\Fcm\Resources\ApnsConfig;


class PushNotification extends Notification
{
    use Queueable;

    public $title;
    public $message;
    public $imageUrl;
    public $actionUrl;
    public $category;
    public $priority;

    /**
     * Create a new notification instance.
     */
    public function __construct($title, $message, $options = [])
    {
        $this->title = $title;
        $this->message = $message;

        // Use optimized image for better display
        $this->imageUrl = $options['image'] ?? $this->getDefaultImage();
        $this->actionUrl = $options['action_url'] ?? null;
        $this->category = $options['category'] ?? 'general';
        $this->priority = 'high';
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    /**
     * Build FCM message with optimized image handling
     */
    public function toFcm($notifiable)
    {
        $message = FcmMessage::create()
            ->setNotification(
                \NotificationChannels\Fcm\Resources\Notification::create()
                    ->setTitle($this->title)
                    ->setBody($this->message)
                    ->setImage($this->imageUrl) // Large image (shown below text)
            )
            ->setData([
                'click_action' => $this->actionUrl ?? 'FLUTTER_NOTIFICATION_CLICK',
                'category' => $this->category,
                'image_url' => $this->imageUrl,
                'title' => $this->title,
                'body' => $this->message,
                'sound' => 'default',
            ]);

        // Android-specific configuration
        $message->setAndroid(
            AndroidConfig::create()
                ->setPriority($this->getAndroidPriority())
                ->setNotification(
                    AndroidNotification::create()
                        ->setTitle($this->title)
                        ->setBody($this->message)
                        ->setIcon('ic_notification') // Use app's default notification icon
                        ->setImage($this->imageUrl) // Large image (below text)
                        ->setColor('#FF6B35')
                        ->setSound('default')
                        ->setTag($this->category)
                        ->setChannelId($this->getChannelId())
                        ->setClickAction($this->actionUrl ?? 'FLUTTER_NOTIFICATION_CLICK')

                )
                ->setCollapseKey($this->category)
        );

        // iOS-specific configuration (if needed)
        if (class_exists(ApnsConfig::class)) {
            $message->setApns(
                ApnsConfig::create()
                    ->setHeaders([
                        'apns-priority' => $this->priority === 'high' ? '10' : '5',
                    ])
                    ->setPayload([
                        'aps' => [
                            'alert' => [
                                'title' => $this->title,
                                'body' => $this->message,
                            ],
                            'badge' => 1,
                            'sound' => 'default',
                            'category' => $this->category,
                        ],
                        'image_url' => $this->imageUrl,
                        'click_action' => $this->actionUrl,
                    ])
            );
        }

        return $message;
    }

    /**
     * Get default image URL
     */
    private function getDefaultImage(): string
    {
        // Use a properly sized image (recommended: 1024x512px for Android)
        return url('logo.jpg');
    }

    /**
     * Get Android priority
     */
    private function getAndroidPriority(): AndroidMessagePriority
    {
        return $this->priority === 'high'
            ? AndroidMessagePriority::HIGH
            : AndroidMessagePriority::NORMAL;
    }

    /**
     * Get notification channel ID based on category
     */
    private function getChannelId(): string
    {
        $channels = [
            'urgent' => 'urgent_notifications',
            'promotional' => 'promotional_notifications',
            'general' => 'general_notifications',
            'chat' => 'chat_notifications',
        ];

        return $channels[$this->category] ?? 'general_notifications';
    }

    /**
     * Static method for quick notification creation
     */
    public static function create($title, $message, $options = []): self
    {
        return new self($title, $message, $options);
    }

    /**
     * Validate image URL before sending
     */
    public function validateImage(): bool
    {
        return $this->isValidUrl($this->imageUrl);
    }

    /**
     * Check if URL is valid and accessible
     */
    private function isValidUrl($url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

//class PushNotification extends Notification
//{
//    use Queueable;
//
//    public $title;
//    public $message;
//    public $imageUrl;
//    public $actionUrl;
//    public $category;
//
//    /**
//     * Create a new notification instance.
//     */
//    public function __construct($title, $message, $options = [])
//    {
//        $this->title = $title;
//        $this->message = $message;
//        $this->imageUrl = $options['image'] ?? url('logo.jpg');
//        $this->actionUrl = $options['action_url'] ?? null;
//        $this->category = $options['category'] ?? 'general';
//    }
//
//    /**
//     * Get the notification's delivery channels.
//     */
//    public function via(object $notifiable): array
//    {
//        return [FcmChannel::class];
//    }
//
//    public function toFcm($notifiable)
//    {
//        return FcmMessage::create()
//            ->setNotification(
//                \NotificationChannels\Fcm\Resources\Notification::create()
//                    ->setTitle($this->title)
//                    ->setBody($this->message)
//                    ->setImage($this->imageUrl)
//            )
//            ->setData([
//                'click_action' => $this->actionUrl ?? 'FLUTTER_NOTIFICATION_CLICK',
//                'category' => $this->category,
//                'image_url' => $this->imageUrl,
//            ])
//            ->setAndroid(
//                AndroidConfig::create()
//                    ->setPriority(AndroidMessagePriority::HIGH)
//                    ->setNotification(
//                        AndroidNotification::create()
//                            ->setTitle($this->title)
//                            ->setBody($this->message)
//                            ->setIcon('logo.jpg')
//                            ->setColor('#FF6B35')
//                            ->setImage(url('logo.jpg'))
//                            ->setSound('default')
//                            ->setTag($this->category)
//                            ->setChannelId('high_importance_channel')
//                            ->setClickAction($this->actionUrl ?? 'FLUTTER_NOTIFICATION_CLICK')
//                    )
//            );
//    }
//}
