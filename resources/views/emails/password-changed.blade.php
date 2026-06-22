@extends('emails.layout')

@section('title', 'Contraseña cambiada — FlexDash')

@section('content')
    <h1>Tu contraseña ha sido cambiada</h1>
    <p>Te informamos que la contraseña de tu cuenta de FlexDash ha sido modificada con éxito.</p>
    
    <div class="info-card">
        <p><strong>Detalles de la actividad:</strong></p>
        <p>• <strong>Fecha y hora:</strong> {{ $dateTime }} (UTC/Local)</p>
        <p>• <strong>Estado:</strong> Completado con éxito</p>
    </div>
    
    <p>Si has sido tú quien ha realizado este cambio, puedes ignorar este mensaje de forma segura.</p>
    
    <div style="background-color: #FEF2F2; border-left: 4px solid #EF4444; padding: 16px; margin: 20px 0; border-radius: 0 6px 6px 0; color: #991B1B;">
        <p style="margin: 0; font-size: 14px; font-weight: 600;">¿No fuiste tú?</p>
        <p style="margin: 4px 0 0 0; font-size: 13px;">Si no has cambiado tu contraseña, es posible que alguien haya accedido a tu cuenta sin autorización. Por favor, restablece tu contraseña inmediatamente desde nuestra página de inicio o ponte en contacto directo con soporte técnico en <a href="mailto:soporte@flexdash.app" style="color: #991B1B; text-decoration: underline; font-weight: 600;">soporte@flexdash.app</a>.</p>
    </div>
@endsection
