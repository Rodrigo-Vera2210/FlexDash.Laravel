<?php

namespace App\Modules\Sale\Tests\Feature;

use App\Models\User;
use App\Models\Tax;
use App\Modules\Partner\Models\Partner;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalePdfTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $jwtToken;
    private Sale $sale;

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

        $client = Partner::create([
            'type' => 'cliente',
            'business_name' => 'Cliente Test S.A.',
            'document_type' => 'RUC',
            'document_number' => '20123456789',
        ]);

        // Create a test sale
        $this->sale = Sale::create([
            'partner_id' => $client->id,
            'user_id' => $this->user->id,
            'tax_id' => $tax->id,
            'series' => 'F001',
            'number' => '000001',
            'issue_date' => now()->format('Y-m-d'),
            'status' => Sale::STATUS_APPROVED,
            'total' => 20,
            'subtotal' => 16.95,
            'tax_amount' => 3.05,
            'paid_amount' => 0,
            'pending_balance' => 20,
        ]);

        SaleDetail::create([
            'sale_id' => $this->sale->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 20,
            'cost_price' => 10,
            'subtotal' => 20,
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

    public function test_authenticated_user_can_download_sale_pdf(): void
    {
        $response = $this->authGet("/sales/{$this->sale->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_invalid_sale_returns_404(): void
    {
        $response = $this->authGet("/sales/99999/pdf");

        $response->assertStatus(404);
    }

    public function test_unauthenticated_user_cannot_download_sale_pdf(): void
    {
        $response = $this->get("/sales/{$this->sale->id}/pdf");

        $response->assertRedirect();
    }
}
