<?php

namespace App\Modules\Purchase\Tests\Feature;

use App\Models\User;
use App\Models\Tax;
use App\Modules\Partner\Models\Partner;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePdfTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $jwtToken;
    private Purchase $purchase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->jwtToken = $this->generateJwt($this->user);

        // Seed basic tax and product
        $tax = Tax::create(['name' => 'IGV', 'code' => 'IGV18', 'rate' => 18]);
        $category = Category::create(['name' => 'General']);
        $product = Product::create([
            'category_id' => $category->id,
            'tax_id' => $tax->id,
            'code' => 'P001',
            'name' => 'Test Product',
            'cost' => 10,
            'price' => 20,
            'stock' => 100,
        ]);

        $supplier = Partner::create([
            'type' => 'proveedor',
            'business_name' => 'Proveedor Test S.A.',
            'document_type' => 'RUC',
            'document_number' => '20987654321',
        ]);

        // Create a test purchase
        $this->purchase = Purchase::create([
            'partner_id' => $supplier->id,
            'user_id' => $this->user->id,
            'tax_id' => $tax->id,
            'series' => 'C001',
            'number' => '000001',
            'issue_date' => now()->format('Y-m-d'),
            'status' => Purchase::STATUS_APPROVED,
            'total' => 10,
            'subtotal' => 8.47,
            'tax_amount' => 1.53,
            'paid_amount' => 0,
            'pending_balance' => 10,
        ]);

        PurchaseDetail::create([
            'purchase_id' => $this->purchase->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_cost' => 10,
            'subtotal' => 10,
        ]);
    }

    private function generateJwt(User $user): string
    {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $secret = config('app.key') ?: env('APP_KEY', 'secret');
        if (str_starts_with($secret, 'base64:')) {
            $secret = base64_decode(substr($secret, 7));
        }

        $payload = json_encode([
            'user_id' => $user->id,
            'role'    => $user->role ?? 'user',
            'iat'     => time(),
            'exp'     => time() + 86400,
        ]);

        $base64url = fn($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        $segments   = [$base64url($header), $base64url($payload)];
        $signature  = hash_hmac('sha256', implode('.', $segments), $secret, true);
        $segments[] = $base64url($signature);

        return implode('.', $segments);
    }

    private function authGet(string $uri)
    {
        return $this->withCookie('token', $this->jwtToken)->get($uri);
    }

    public function test_authenticated_user_can_download_purchase_pdf(): void
    {
        $response = $this->authGet("/purchases/{$this->purchase->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_invalid_purchase_returns_404(): void
    {
        $response = $this->authGet("/purchases/99999/pdf");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_download_purchase_pdf(): void
    {
        $response = $this->get("/purchases/{$this->purchase->id}/pdf");

        $response->assertRedirect();
    }
}
