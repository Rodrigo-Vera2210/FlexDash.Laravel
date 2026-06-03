<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Http\Middleware\EnsureEmailVerified;

class RequireEmailVerifiedMiddlewareTest extends TestCase
{
    public function test_middleware_blocks_unverified_user()
    {
        // Create a lightweight user object with email_verified_at null
        $user = new \App\Models\User();
        $user->email_verified_at = null;

        $request = Request::create('/dummy', 'GET');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $middleware = new EnsureEmailVerified();

        $response = $middleware->handle($request, function () {
            return response('ok');
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertStringContainsString('email_not_verified', $response->getContent());
    }
}
