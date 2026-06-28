<?php

namespace App\Modules\Tickets\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Requests\CreateTicketRequest;
use App\Modules\Tickets\Requests\StoreMessageRequest;
use App\Modules\Tickets\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function __construct(
        protected TicketService $ticketService
    ) {}

    public function index()
    {
        $tickets = Ticket::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tickets.index', compact('tickets'));
    }

    public function create(Request $request)
    {
        $prefilledTitle = $request->query('title', '');
        $prefilledDescription = $request->query('description', '');
        $prefilledTrace = $request->query('error_trace', '');

        return view('tickets.create', [
            'prefilledTitle'       => $prefilledTitle,
            'prefilledDescription' => $prefilledDescription,
            'prefilledTrace'       => $prefilledTrace,
        ]);
    }

    public function store(CreateTicketRequest $request)
    {
        try {
            $this->ticketService->createTicket(
                $request->user(),
                $request->only(['title', 'description', 'severity', 'error_trace']),
                $request->file('evidence', [])
            );

            return redirect()->route('tickets.index')->with('status', 'Ticket reportado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['form' => $e->getMessage()])->withInput();
        }
    }

    public function show(Ticket $ticket)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado.');
        }

        return view('tickets.show', compact('ticket'));
    }

    public function storeMessage(Ticket $ticket, StoreMessageRequest $request)
    {
        if ($ticket->user_id !== Auth::id()) {
            abort(403, 'Acceso denegado.');
        }

        $this->ticketService->addMessage(
            $request->user(),
            $ticket,
            $request->validated()['message']
        );

        return back()->with('status', 'Mensaje enviado exitosamente.');
    }
}
