<?php

namespace App\Modules\SuperAdmin\Notifications;

use App\Models\SubscriptionPayment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentRejectedNotification extends Notification
{
    public function __construct(
        public readonly SubscriptionPayment $payment,
        public readonly string $rejectionReason
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
            ->subject('❌ Pago rechazado — FlexDash')
            ->view('emails.payment-rejected', [
                'rejectionReason' => $this->rejectionReason,
            ]);
    }
}
