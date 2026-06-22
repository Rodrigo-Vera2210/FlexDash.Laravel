@extends('emails.layout')

@section('title', '❌ Pago Rechazado — FlexDash')

@section('content')
    <h1>Tu pago ha sido rechazado</h1>
    <p>Te informamos que, tras revisar el comprobante de pago enviado para tu suscripción, este ha sido rechazado por el superadministrador.</p>
    
    <div style="background-color: #FEF2F2; border-left: 4px solid #EF4444; padding: 16px; margin: 20px 0; border-radius: 0 6px 6px 0; color: #991B1B;">
        <p style="margin: 0; font-size: 14px; font-weight: 600;">Motivo del rechazo:</p>
        <p style="margin: 4px 0 0 0; font-size: 14px; font-style: italic;">"{{ $rejectionReason }}"</p>
    </div>
    
    <p>Para evitar interrupciones en tu servicio o reactivar tu cuenta, por favor sube un nuevo comprobante de pago válido a la brevedad.</p>
    
    <div class="btn-container">
        <a href="{{ route('subscription.suspended') }}" class="btn">Subir Nuevo Comprobante</a>
    </div>
@endsection
