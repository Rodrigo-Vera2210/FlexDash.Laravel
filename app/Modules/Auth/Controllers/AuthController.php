<?php

namespace App\Modules\Auth\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Las credenciales proporcionadas son incorrectas.']);
        }

        if (empty($user->email_verified_at)) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'email_not_verified'], 403);
            }
            $request->session()->put('registered_user_id', $user->id);
            return redirect()->route('registration.verify-otp.show')
                ->with('status', 'Por favor, verifica tu correo electrónico con el código OTP enviado.');
        }

        if ($user->company_id && $user->status !== 'active' && $user->role !== 'superadmin') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'account_inactive'], 403);
            }
            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Su cuenta está inactiva o pendiente de activación.']);
        }

        // Issue a simple HS256 JWT (header.payload.signature) using APP_KEY.
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);

        $secret = config('app.key') ?: env('APP_KEY', 'secret');
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        $payload = json_encode([
            'user_id' => $user->id,
            'role' => $user->role ?? 'user',
            'iat' => time(),
            'exp' => time() + 86400, // 24 hours
        ]);

        $base64url = function ($data) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        };

        $segments = [];
        $segments[] = $base64url($header);
        $segments[] = $base64url($payload);
        $signingInput = implode('.', $segments);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);
        $segments[] = $base64url($signature);

        $jwt = implode('.', $segments);

        if ($request->expectsJson()) {
            return response()->json(['token' => $jwt], 200)
                ->cookie('token', $jwt, 1440, null, null, false, true);
        }

        if ($user->role === 'superadmin') {
            return redirect()->route('superadmin.dashboard')
                ->cookie('token', $jwt, 1440, null, null, false, true);
        }

        return redirect()->route('dashboard')
            ->cookie('token', $jwt, 1440, null, null, false, true);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $cookie = cookie()->forget('token');

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Sesión cerrada con éxito'])->withCookie($cookie);
        }

        return redirect()->route('login')->withCookie($cookie);
    }
}
