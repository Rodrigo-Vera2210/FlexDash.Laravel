<?php

namespace App\Modules\Registration\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiryNotification extends Notification
{
    public function __construct(
        public readonly int $daysRemaining,
        public readonly string $expiresAt
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Tu suscripción de FlexDash está por vencer')
            ->view('emails.subscription-expiry', [
                'daysRemaining' => $this->daysRemaining,
                'expiresAt'     => $this->expiresAt,
            ]);
    }
}
