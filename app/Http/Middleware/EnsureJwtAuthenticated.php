<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class EnsureJwtAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Try to extract token from Authorization header or cookie 'token'
        $token = $request->bearerToken();

        if (!$token) {
            $token = $request->cookie('token');
        }

        if (!$token) {
            Log::warning('JWT Middleware: Request rejected, no token provided.');
            return $this->unauthorizedResponse($request);
        }

        // 2. Parse token segments
        $segments = explode('.', $token);
        if (count($segments) !== 3) {
            Log::warning('JWT Middleware: Request rejected, malformed token.');
            return $this->unauthorizedResponse($request);
        }

        [$headerSegment, $payloadSegment, $signatureSegment] = $segments;

        // 3. Verify signature
        $secret = config('app.key') ?: env('APP_KEY', 'secret');
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        $signingInput = $headerSegment . '.' . $payloadSegment;
        
        $base64urlDecode = function ($data) {
            $remainder = strlen($data) % 4;
            if ($remainder) {
                $padlen = 4 - $remainder;
                $data .= str_repeat('=', $padlen);
            }
            return base64_decode(strtr($data, '-_', '+/'));
        };

        $signature = $base64urlDecode($signatureSegment);
        $expectedSignature = hash_hmac('sha256', $signingInput, $secret, true);

        if (!hash_equals($signature, $expectedSignature)) {
            Log::warning('JWT Middleware: Request rejected, invalid signature.');
            return $this->unauthorizedResponse($request);
        }

        // 4. Verify expiration
        $payload = json_decode($base64urlDecode($payloadSegment), true);
        if (!$payload || !isset($payload['user_id']) || !isset($payload['exp'])) {
            Log::warning('JWT Middleware: Request rejected, invalid payload.');
            return $this->unauthorizedResponse($request);
        }

        if (time() >= $payload['exp']) {
            Log::warning("JWT Middleware: Request rejected, token expired (exp: {$payload['exp']}).");
            return $this->unauthorizedResponse($request);
        }

        // 5. Retrieve and authenticate user
        $user = User::find($payload['user_id']);
        if (!$user) {
            Log::warning("JWT Middleware: Request rejected, user ID {$payload['user_id']} not found.");
            return $this->unauthorizedResponse($request);
        }

        // Check if verified
        if (empty($user->email_verified_at)) {
            Log::warning("JWT Middleware: Request rejected, user ID {$payload['user_id']} is unverified.");
            return $this->unauthorizedResponse($request);
        }

        // Resolve request user and authenticate in standard guard session
        $request->setUserResolver(fn() => $user);
        Auth::login($user);

        // Check subscription status and user status for non-superadmins with a company
        if ($user->role !== 'superadmin' && $user->company_id) {
            $company = $user->company;
            $isSuspended = !$company || in_array($company->subscription_status, ['pending_approval', 'inactive', 'rejected', 'suspended']);
            if (!$isSuspended && !empty($company->subscription_expires_at) && now()->greaterThan($company->subscription_expires_at)) {
                $isSuspended = true;
            }
            if ($user->status !== 'active') {
                $isSuspended = true;
            }

            if ($isSuspended) {
                // Allow logout or suspension view to load without redirection loop
                if (!$request->routeIs('logout') && !$request->is('logout') && !$request->routeIs('subscription.suspended') && !$request->is('subscription-suspended')) {
                    if ($request->expectsJson() || $request->wantsJson() || $request->ajax()) {
                        return response()->json(['error' => 'subscription_inactive'], 403);
                    }
                    return redirect()->route('subscription.suspended');
                }
            }
        }

        return $next($request);
    }

    /**
     * Build the unauthorized response based on request format expectation.
     */
    protected function unauthorizedResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return redirect()->guest(route('login'));
    }
}
