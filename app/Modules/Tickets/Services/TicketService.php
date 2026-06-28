<?php

namespace App\Modules\Tickets\Services;

use App\Models\User;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketMessage;
use App\Modules\Tickets\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;

class TicketService
{
    /**
     * Create a new ticket with mandatory attachments.
     */
    public function createTicket(User $user, array $data, array $images): Ticket
    {
        if (empty($images)) {
            throw new \Exception("Debe adjuntar al menos una imagen de evidencia.");
        }

        $ticket = Ticket::create([
            'user_id'     => $user->id,
            'company_id'  => $user->company_id,
            'title'       => $data['title'],
            'description' => $data['description'],
            'severity'    => $data['severity'] ?? 'bajo',
            'status'      => 'pendiente',
            'error_trace' => $data['error_trace'] ?? null,
        ]);

        foreach ($images as $image) {
            $path = $image->store('tickets/evidence', 'public');
            TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'file_path' => $path,
            ]);
        }

        return $ticket;
    }

    /**
     * Add a message to the ticket thread.
     */
    public function addMessage(User $user, Ticket $ticket, string $messageContent): TicketMessage
    {
        return TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'message'   => $messageContent,
        ]);
    }

    /**
     * Update ticket status (with business rule validation for superadmins).
     */
    public function updateStatus(User $user, Ticket $ticket, string $newStatus): Ticket
    {
        if (!in_array($newStatus, ['pendiente', 'en proceso', 'rechazado', 'aprobado'])) {
            throw new \Exception("Estado inválido.");
        }

        if (in_array($newStatus, ['rechazado', 'aprobado'])) {
            // Check if there is at least one message written by a superadmin
            $hasSuperAdminReply = $ticket->messages()
                ->whereHas('user', function ($query) {
                    $query->where('role', 'superadmin');
                })
                ->exists();

            if (!$hasSuperAdminReply) {
                throw new \Exception("Para aprobar o rechazar un ticket, primero debe responder con un mensaje al usuario.");
            }
        }

        $ticket->update(['status' => $newStatus]);

        return $ticket;
    }
}
