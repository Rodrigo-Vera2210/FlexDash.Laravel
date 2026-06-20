<?php

namespace App\Modules\Registration\Controllers;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;

class SubscriptionBillingController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $company = $user->company;

        // Fetch pending or latest payments for this company
        $payments = SubscriptionPayment::where('company_id', $company->id)
            ->latest()
            ->get();

        return view('settings.subscription', compact('company', 'payments'));
    }

    public function storePayment(Request $request)
    {
        $request->validate([
            'plan'                => ['required', 'string', 'in:basic,standard'],
            'bank_origin'         => ['required', 'string', 'max:255'],
            'account_destination' => ['required', 'string', 'max:255'],
            'payment_receipt'     => ['required', 'file', 'image', 'max:4096'],
            'type'                => ['required', 'string', 'in:upgrade,renewal'],
        ]);

        $path = $request->file('payment_receipt')->store('receipts', 'public');

        SubscriptionPayment::create([
            'company_id'          => auth()->user()->company_id,
            'plan'                => $request->plan,
            'bank_origin'         => $request->bank_origin,
            'account_destination' => $request->account_destination,
            'receipt_path'        => $path,
            'status'              => 'pending',
            'type'                => $request->type,
        ]);

        return redirect()->route('settings.subscription.index')
            ->with('status', 'Comprobante de pago enviado con éxito. Su solicitud está en espera de aprobación por el Superadministrador.');
    }
}
