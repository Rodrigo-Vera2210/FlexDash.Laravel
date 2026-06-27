@extends('layouts.app')

@section('title', 'Mi Perfil')
@section('page-title', 'Configuración de Cuenta')
@section('page-subtitle', 'Actualiza tu información personal, cambia tu contraseña o gestiona tu cuenta')

@section('content')
<div class="mt-2 max-w-4xl mx-auto space-y-6 page-fade">
    
    <div class="card-panel p-6">
        <div class="max-w-xl">
            @include('profile.partials.update-profile-information-form')
        </div>
    </div>

    <div class="card-panel p-6">
        <div class="max-w-xl">
            @include('profile.partials.update-password-form')
        </div>
    </div>

    <div class="card-panel p-6 border-l-4 border-l-[color:var(--danger)]">
        <div class="max-w-xl">
            @include('profile.partials.delete-user-form')
        </div>
    </div>

</div>
@endsection
