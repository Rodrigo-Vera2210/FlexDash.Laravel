<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Registration\Models\EmailVerification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordChangeOtpModalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test password change page is accessible
     */
    public function test_password_change_page_accessible(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('password.change'));

        $response->assertStatus(200);
        $response->assertSee('Cambiar Contraseña');
        $response->assertSee('Contraseña Actual');
    }

    /**
     * Test OTP request error handling in page flow
     */
    public function test_otp_request_error_handling_page_flow(): void
    {
        $user = User::factory()->create(['password' => 'correctpassword']);

        $response = $this->actingAs($user)->post(route('password.change.submit'), [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
    }

    /**
     * Test OTP verification displays error for invalid code
     */
    public function test_otp_verification_error_for_invalid_code_page_flow(): void
    {
        $user = User::factory()->create();

        EmailVerification::create([
            'user_id' => $user->id,
            'verification_code' => Hash::make('000000'),
            'purpose' => 'password_change',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $this->actingAs($user)->withSession(['password_change_new_' . $user->id => 'newpassword123']);

        $response = $this->post(route('password.change.verify.submit'), [
            'otp_code' => '111111',
        ]);

        $response->assertSessionHasErrors('otp_code');
    }

    /**
     * Test page flow tracks OTP attempts in database
     */
    public function test_otp_attempts_tracked_in_database_page_flow(): void
    {
        $user = User::factory()->create();

        $verification = EmailVerification::create([
            'user_id' => $user->id,
            'verification_code' => Hash::make('000000'),
            'purpose' => 'password_change',
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $this->actingAs($user)->withSession(['password_change_new_' . $user->id => 'newpassword123']);

        // First failed attempt
        $this->post(route('password.change.verify.submit'), [
            'otp_code' => '111111',
        ]);

        $verification->refresh();
        $this->assertEquals(1, $verification->attempts);
    }

    /**
     * Test success response after password reset
     */
    public function test_success_response_after_password_reset_page_flow(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);

        $this->actingAs($user)->post(route('password.change.submit'), [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $verification = EmailVerification::where('user_id', $user->id)->first();
        
        $this->assertNotNull($verification);
        $this->assertEquals('password_change', $verification->purpose);
    }

    /**
     * Test successful password change redirects to profile page
     */
    public function test_password_change_success_redirects_to_profile(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);

        $this->actingAs($user)->post(route('password.change.submit'), [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $verification = EmailVerification::where('user_id', $user->id)->first();
        $otp = '123456';
        $verification->update([
            'verification_code' => Hash::make($otp),
        ]);

        $response = $this->post(route('password.change.verify.submit'), [
            'otp_code' => $otp,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'password-updated');

        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }
}
