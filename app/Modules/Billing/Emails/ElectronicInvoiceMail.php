<?php

namespace App\Modules\Billing\Emails;

use App\Modules\Billing\Models\ElectronicInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ElectronicInvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public readonly ElectronicInvoice $invoice
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Comprobante Electrónico Autorizado - No. ' . $this->invoice->sequence,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.layout',
            with: [
                'headerText' => 'Comprobante Electrónico',
                'bodyText'   => 'Estimado cliente, adjunto a este correo encontrará su comprobante electrónico autorizado por el SRI correspondiente a su transacción.',
                'btnUrl'     => '',
                'btnText'    => '',
                'extraInfo'  => 'Número de comprobante: ' . $this->invoice->sequence . "\nClave de acceso: " . $this->invoice->access_key,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        if ($this->invoice->xml_path && Storage::exists($this->invoice->xml_path)) {
            $attachments[] = Attachment::fromPath(Storage::path($this->invoice->xml_path))
                ->as($this->invoice->access_key . '.xml')
                ->withMime('application/xml');
        }

        if ($this->invoice->pdf_path && Storage::exists($this->invoice->pdf_path)) {
            $attachments[] = Attachment::fromPath(Storage::path($this->invoice->pdf_path))
                ->as('factura_' . $this->invoice->sequence . '.pdf')
                ->withMime('application/pdf');
        }

        return $attachments;
    }
}
