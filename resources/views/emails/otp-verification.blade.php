@extends('emails.layout')

@section('title', 'Verifica tu cuenta — FlexDash')

@section('content')
    <h1>¡Hola, {{ $userName }}!</h1>
    <p>Gracias por registrarte en FlexDash. Para completar tu registro y activar tu cuenta, por favor introduce el siguiente código de verificación en el formulario:</p>
    
    <div class="code-box">
        {{ $otpCode }}
    </div>
    
    <p style="text-align: center; font-size: 13px; color: #6B7280; margin-top: -10px;">
        Este código es válido por <strong>{{ $expiresIn }}</strong>.
    </p>
    
    <div class="info-card">
        <p><strong>Nota de seguridad:</strong> El equipo de FlexDash nunca te pedirá tu contraseña o este código de verificación por correo electrónico ni por otro medio de comunicación.</p>
        <p>Si no has solicitado este registro o crees que se trata de un error, puedes ignorar este correo de forma segura.</p>
    </div>
@endsection
