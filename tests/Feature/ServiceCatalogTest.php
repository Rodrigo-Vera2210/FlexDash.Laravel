<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tax;
use App\Modules\Service\Models\Service;
use App\Modules\Service\Models\ServiceCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function generateJwtForUser(User $user, int $expiryOffset = 86400): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode([
            'user_id' => $user->id,
            'role'    => $user->role ?? 'user',
            'iat'     => time(),
            'exp'     => time() + $expiryOffset,
        ]);

        $secret = config('app.key') ?: env('APP_KEY', 'secret');
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        $base64url = function ($data) {
            return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        };

        $signingInput = $base64url($header) . '.' . $base64url($payload);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);

        return $base64url($header) . '.' . $base64url($payload) . '.' . $base64url($signature);
    }

    protected function getAuthUser()
    {
        $company = \App\Modules\Registration\Models\Company::create([
            'name' => 'Test Company',
            'tax_id' => '1799999999001',
            'company_type' => 'legal_entity',
            'subscription_plan' => 'premium',
            'subscription_status' => 'active',
            'city' => 'Quito',
            'state_province' => 'Pichincha',
            'postal_code' => '170150',
            'country' => 'Ecuador',
        ]);

        $user = User::create([
            'name'              => 'Authenticated User',
            'email'             => 'auth@example.com',
            'password'          => Hash::make('password'),
            'status'            => 'active',
            'company_id'        => $company->id,
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    public function test_can_create_service_category()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/settings/catalogs/service-categories', [
                'name' => 'Instalaciones',
                'description' => 'Servicios de instalación a domicilio',
            ]);

        $response->assertRedirect('/settings/catalogs');
        $this->assertDatabaseHas('service_categories', ['name' => 'Instalaciones', 'company_id' => $user->company_id]);
    }

    public function test_can_create_service()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $sc = ServiceCategory::create([
            'company_id' => $user->company_id,
            'name' => 'Instalaciones',
            'is_active' => true
        ]);
        
        $tax = Tax::create([
            'company_id' => $user->company_id,
            'name' => 'IVA 12',
            'code' => 'IVA12',
            'rate' => 12.00,
            'is_active' => true
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->post('/services', [
                'code' => 'SERV-001',
                'name' => 'Instalación Aire Acondicionado',
                'description' => 'Servicio completo de instalación',
                'service_category_id' => $sc->id,
                'tax_id' => $tax->id,
                'price' => 120.00,
                'cost' => 45.00,
                'is_active' => 1,
            ]);

        $service = Service::where('code', 'SERV-001')->first();
        $response->assertRedirect('/services/' . $service->id);
        $this->assertDatabaseHas('services', [
            'company_id' => $user->company_id,
            'code' => 'SERV-001',
            'name' => 'Instalación Aire Acondicionado',
            'price' => 120.00,
            'cost' => 45.00,
        ]);
    }

    public function test_can_update_service()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $service = Service::create([
            'company_id' => $user->company_id,
            'code' => 'SERV-002',
            'name' => 'Servicio Antiguo',
            'price' => 50.00,
            'cost' => 10.00,
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->put("/services/{$service->id}", [
                'code' => 'SERV-002-MOD',
                'name' => 'Servicio Actualizado',
                'price' => 65.00,
                'cost' => 15.00,
                'is_active' => 1,
            ]);

        $response->assertRedirect('/services/' . $service->id);
        $this->assertDatabaseHas('services', [
            'id' => $service->id,
            'code' => 'SERV-002-MOD',
            'name' => 'Servicio Actualizado',
            'price' => 65.00,
        ]);
    }

    public function test_can_soft_delete_service()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $service = Service::create([
            'company_id' => $user->company_id,
            'code' => 'SERV-003',
            'name' => 'Mantenimiento preventivo',
            'price' => 80.00,
            'cost' => 20.00,
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->delete("/services/{$service->id}");

        $response->assertRedirect('/services');
        $this->assertSoftDeleted('services', ['id' => $service->id]);
    }

    public function test_toggle_status_of_service_category()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $sc = ServiceCategory::create([
            'company_id' => $user->company_id,
            'name' => 'Soporte Técnico',
            'is_active' => true
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/settings/catalogs/toggle-status', [
                'model' => 'service_category',
                'id' => $sc->id,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_active' => false]);
        $this->assertDatabaseHas('service_categories', ['id' => $sc->id, 'is_active' => false]);
    }

    public function test_cannot_delete_service_category_with_referenced_services()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $sc = ServiceCategory::create([
            'company_id' => $user->company_id,
            'name' => 'Consultoría',
            'is_active' => true
        ]);
        
        Service::create([
            'company_id' => $user->company_id,
            'service_category_id' => $sc->id,
            'code' => 'CONS-001',
            'name' => 'Asesoría Tributaria',
            'price' => 150.00,
            'is_active' => true,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->delete("/settings/catalogs/service-categories/{$sc->id}");

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('service_categories', ['id' => $sc->id]); // category is NOT deleted
    }
}
