<?php

namespace App\Modules\Registration\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailOtpNotification extends Notification
{
    public function __construct(
        public readonly string $otpCode,
        public readonly int $expiresInMinutes = 1440,
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
        $expiresInHours = $this->expiresInMinutes / 60;
        $expiryLabel = $expiresInHours >= 1
            ? round($expiresInHours) . ' hour(s)'
            : $this->expiresInMinutes . ' minute(s)';

        return (new MailMessage)
            ->subject('Verify your FlexDash account')
            ->greeting("Hello, {$notifiable->name}!")
            ->line('To complete your registration, please enter the following verification code:')
            ->line("**{$this->otpCode}**")
            ->line("This code expires in {$expiryLabel}.")
            ->line('If you did not request this, you can safely ignore this email.');
    }
}
