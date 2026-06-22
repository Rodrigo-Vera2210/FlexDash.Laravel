@extends('emails.layout')

@section('title', 'Restablece tu contraseña — FlexDash')

@section('content')
    <h1>Restablecer tu contraseña</h1>
    <p>Has recibido este correo porque hemos recibido una solicitud de restablecimiento de contraseña para tu cuenta de FlexDash.</p>
    
    <div class="btn-container">
        <a href="{{ $resetUrl }}" class="btn">Restablecer Contraseña</a>
    </div>
    
    <p style="text-align: center; font-size: 13px; color: #6B7280; margin-top: -10px;">
        Este enlace de restablecimiento de contraseña expirará en <strong>60 minutos</strong>.
    </p>
    
    <div class="info-card">
        <p>Si no realizaste esta solicitud, no es necesario realizar ninguna acción adicional. Tu cuenta sigue estando segura.</p>
    </div>
    
    <p style="font-size: 12px; color: #9CA3AF; border-top: 1px solid #E5E7EB; padding-top: 16px; margin-top: 24px;">
        Si tienes problemas para hacer clic en el botón "Restablecer Contraseña", copia y pega la siguiente URL en tu navegador web:
        <br>
        <span style="word-break: break-all; color: #0A7EA5;">{{ $resetUrl }}</span>
    </p>
@endsection
