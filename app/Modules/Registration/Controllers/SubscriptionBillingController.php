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

    public function storePaymentSuspended(Request $request)
    {
        $request->validate([
            'plan'                => ['required', 'string', 'in:basic,standard'],
            'bank_origin'         => ['required', 'string', 'max:255'],
            'account_destination' => ['required', 'string', 'max:255'],
            'payment_receipt'     => ['required', 'file', 'image', 'max:4096'],
        ]);

        $user = auth()->user();
        $company = $user->company;

        // Auto determine transaction type
        $type = ($request->plan === $company->subscription_plan) ? 'renewal' : 'upgrade';

        $path = $request->file('payment_receipt')->store('receipts', 'public');

        SubscriptionPayment::create([
            'company_id'          => $company->id,
            'plan'                => $request->plan,
            'bank_origin'         => $request->bank_origin,
            'account_destination' => $request->account_destination,
            'receipt_path'        => $path,
            'status'              => 'pending',
            'type'                => $type,
        ]);

        // Transition company status to 'pending_approval' so it reflects checking/verification screen
        $company->subscription_status = 'pending_approval';
        $company->save();

        return redirect()->route('subscription.suspended')
            ->with('status', 'Comprobante de pago enviado con éxito. Su cuenta se reactivará una vez que el pago sea aprobado por el Superadministrador.');
    }

    public function showReceipt($filename)
    {
        $user = auth()->user();
        if (!$user) {
            abort(401);
        }

        // Find the payment by receipt path ending with the filename
        $payment = SubscriptionPayment::where('receipt_path', 'like', '%' . $filename)->first();
        
        if (!$payment) {
            abort(404);
        }

        // Access check: must be superadmin OR belong to the same company
        if ($user->role !== 'superadmin' && $user->company_id !== $payment->company_id) {
            abort(403);
        }

        $path = 'receipts/' . $filename;
        if (!\Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $file = \Illuminate\Support\Facades\Storage::disk('public')->get($path);
        $type = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($path);

        return response($file, 200)->header("Content-Type", $type);
    }
}
