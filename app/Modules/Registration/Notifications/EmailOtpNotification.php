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
            ? round($expiresInHours) . ' ' . (round($expiresInHours) == 1 ? 'hora' : 'horas')
            : $this->expiresInMinutes . ' ' . ($this->expiresInMinutes == 1 ? 'minuto' : 'minutos');

        return (new MailMessage)
            ->subject('Verifica tu cuenta en FlexDash')
            ->view('emails.otp-verification', [
                'otpCode'    => $this->otpCode,
                'userName'   => $notifiable->name,
                'expiresIn'  => $expiryLabel,
            ]);
    }
}
