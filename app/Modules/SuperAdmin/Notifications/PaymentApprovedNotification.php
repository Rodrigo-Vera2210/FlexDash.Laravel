<?php

namespace App\Modules\SuperAdmin\Notifications;

use App\Models\SubscriptionPayment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentApprovedNotification extends Notification
{
    public function __construct(
        public readonly SubscriptionPayment $payment
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
        $company = $this->payment->company;
        $expiresAt = $company && $company->subscription_expires_at
            ? $company->subscription_expires_at->format('d/m/Y')
            : now()->addMonth()->format('d/m/Y');

        return (new MailMessage)
            ->subject('✅ Pago aprobado — Tu suscripción FlexDash está activa')
            ->view('emails.payment-approved', [
                'companyName' => $company?->name ?? 'Tu Empresa',
                'planName'    => $this->payment->plan,
                'expiresAt'   => $expiresAt,
            ]);
    }
}
