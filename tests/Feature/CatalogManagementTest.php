<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tax;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Models\PaymentMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CatalogManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Helper to generate a valid signed JWT for a given user.
     */
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
        $user = User::create([
            'name'              => 'Authenticated User',
            'email'             => 'auth@example.com',
            'password'          => Hash::make('password'),
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    public function test_guest_cannot_access_settings()
    {
        $response = $this->get('/settings/catalogs');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_settings()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('/settings/catalogs');
        $response->assertStatus(200);
        $response->assertViewHasAll(['taxes', 'categories', 'paymentMethods']);
    }

    public function test_can_create_tax_synchronously_and_asynchronously()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        // Test synchronous creation
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->post('/settings/catalogs/taxes', [
            'name' => 'IGV Test',
            'code' => '1000',
            'rate' => 18.00,
        ]);
        $response->assertRedirect('/settings/catalogs');
        $this->assertDatabaseHas('taxes', ['code' => '1000']);

        // Test AJAX creation
        $responseJson = $this->postJson('/settings/catalogs/taxes', [
            'name' => 'IVA Test',
            'code' => '1001',
            'rate' => 12.00,
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $responseJson->assertStatus(201);
        $responseJson->assertJsonFragment(['code' => '1001']);
        $this->assertDatabaseHas('taxes', ['code' => '1001']);
    }

    public function test_can_update_tax()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $tax = Tax::create(['name' => 'Old Tax', 'code' => 'OLD', 'rate' => 10.00, 'is_active' => true]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->put("/settings/catalogs/taxes/{$tax->id}", [
            'name' => 'New Tax Name',
            'code' => 'NEW',
            'rate' => 15.00,
        ]);

        $response->assertRedirect('/settings/catalogs');
        $this->assertDatabaseHas('taxes', ['id' => $tax->id, 'name' => 'New Tax Name', 'code' => 'NEW', 'rate' => 15.00]);
    }

    public function test_can_create_category()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $responseJson = $this->postJson('/settings/catalogs/categories', [
            'name' => 'Tecnología',
            'description' => 'Dispositivos electrónicos',
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);
        $responseJson->assertStatus(201);
        $this->assertDatabaseHas('categories', ['name' => 'Tecnología']);
    }

    public function test_can_toggle_catalog_status()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $cat = Category::create(['name' => 'Bebidas', 'description' => 'Refrescos y aguas', 'is_active' => true]);

        $response = $this->postJson('/settings/catalogs/toggle-status', [
            'model' => 'category',
            'id' => $cat->id,
        ], [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true, 'is_active' => false]);
        $this->assertDatabaseHas('categories', ['id' => $cat->id, 'is_active' => false]);
    }

    public function test_safe_delete_prevents_deleting_referenced_records()
    {
        $user = $this->getAuthUser();
        $token = $this->generateJwtForUser($user);

        $tax = Tax::create(['name' => 'IGV', 'code' => '1000', 'rate' => 18.00, 'is_active' => true]);
        $cat = Category::create(['name' => 'Limpieza', 'description' => 'Detergentes', 'is_active' => true]);

        // Create a product referencing the category and tax
        Product::create([
            'category_id' => $cat->id,
            'tax_id' => $tax->id,
            'code' => 'PROD-TEST',
            'name' => 'Jabón Líquido',
            'unit' => 'UND',
            'cost' => 5.00,
            'price' => 10.00,
            'minimum_stock' => 2.00,
            'is_active' => true,
        ]);

        // Try to delete tax
        $responseTax = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->delete("/settings/catalogs/taxes/{$tax->id}");
        $responseTax->assertSessionHas('error');
        $this->assertDatabaseHas('taxes', ['id' => $tax->id]); // tax is NOT deleted

        // Try to delete category
        $responseCat = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->delete("/settings/catalogs/categories/{$cat->id}");
        $responseCat->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $cat->id]); // category is NOT deleted

        // Now create an unreferenced category and verify it can be deleted
        $unreferencedCat = Category::create(['name' => 'Unused Cat', 'is_active' => true]);
        $responseUnused = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->delete("/settings/catalogs/categories/{$unreferencedCat->id}");
        $responseUnused->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $unreferencedCat->id]);
    }
}
