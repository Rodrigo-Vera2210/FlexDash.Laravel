@extends('emails.layout')

@section('title', '✅ Pago Aprobado — FlexDash')

@section('content')
    <h1>¡Tu pago ha sido aprobado!</h1>
    <p>Nos complace informarte que hemos recibido y verificado correctamente el comprobante de pago de tu suscripción.</p>
    
    <div class="info-card">
        <p><strong>Detalles de la Suscripción:</strong></p>
        <p>• <strong>Empresa:</strong> {{ $companyName }}</p>
        <p>• <strong>Plan:</strong> {{ ucfirst($planName) }}</p>
        <p>• <strong>Fecha de vencimiento:</strong> {{ $expiresAt }}</p>
        <p>• <strong>Estado:</strong> Activo ✅</p>
    </div>
    
    <p>Tu cuenta y la de tus colaboradores ya están completamente habilitadas para operar con todas las herramientas de tu plan.</p>
    
    <div class="btn-container">
        <a href="{{ route('dashboard') }}" class="btn">Ir al Panel de Control</a>
    </div>
@endsection
