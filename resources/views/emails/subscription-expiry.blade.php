@extends('emails.layout')

@section('title', '⚠️ Tu suscripción de FlexDash está por vencer')

@section('content')
    <h1 style="color: #D97706;">¡Tu suscripción está por expirar!</h1>
    <p>Te recordamos que tu suscripción a FlexDash está próxima a vencer.</p>
    
    <div class="info-card" style="border-left-color: #D97706;">
        <p><strong>Detalles del Vencimiento:</strong></p>
        <p>• <strong>Días restantes:</strong> {{ $daysRemaining }} {{ $daysRemaining === 1 ? 'día' : 'días' }}</p>
        <p>• <strong>Fecha límite de pago:</strong> {{ $expiresAt }}</p>
    </div>
    
    <p>Para evitar que tus operaciones se detengan y no perder acceso a tus módulos de venta, inventario y caja chica, te sugerimos realizar el pago de renovación y subir el comprobante.</p>
    
    <div class="btn-container">
        <a href="{{ route('subscription.suspended') }}" class="btn" style="background-color: #D97706; box-shadow: 0 2px 4px rgba(217, 119, 6, 0.2);">Renovar Suscripción</a>
    </div>
@endsection
