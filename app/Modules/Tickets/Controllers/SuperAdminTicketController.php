<?php

namespace App\Modules\Tickets\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Requests\StoreMessageRequest;
use App\Modules\Tickets\Services\TicketService;
use Illuminate\Http\Request;

class SuperAdminTicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService
    ) {}

    public function index(Request $request)
    {
        $severityFilter = $request->query('severity');

        $query = Ticket::orderBy('created_at', 'desc');

        if ($severityFilter && in_array($severityFilter, ['bajo', 'medio', 'alto'])) {
            $query->where('severity', $severityFilter);
        }

        $tickets = $query->get();

        return view('tickets.superadmin.index', compact('tickets', 'severityFilter'));
    }

    public function show(Ticket $ticket)
    {
        return view('tickets.superadmin.show', compact('ticket'));
    }

    public function storeMessage(Ticket $ticket, StoreMessageRequest $request)
    {
        $this->ticketService->addMessage(
            $request->user(),
            $ticket,
            $request->validated()['message']
        );

        // Auto transition status from 'pendiente' to 'en proceso' when superadmin responds
        if ($ticket->status === 'pendiente') {
            $ticket->update(['status' => 'en proceso']);
        }

        return back()->with('status', 'Mensaje enviado exitosamente.');
    }

    public function updateStatus(Ticket $ticket, Request $request)
    {
        $request->validate([
            'status'   => ['nullable', 'string', 'in:pendiente,en proceso,rechazado,aprobado'],
            'severity' => ['nullable', 'string', 'in:bajo,medio,alto'],
        ]);

        try {
            if ($request->filled('severity')) {
                $ticket->update(['severity' => $request->severity]);
            }

            if ($request->filled('status')) {
                $this->ticketService->updateStatus(
                    $request->user(),
                    $ticket,
                    $request->status
                );
            }

            return redirect()->route('superadmin.tickets.show', $ticket)
                ->with('status', 'Ticket actualizado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }
    }
}
