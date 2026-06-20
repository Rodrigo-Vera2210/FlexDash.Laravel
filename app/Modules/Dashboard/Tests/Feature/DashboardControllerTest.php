<?php

namespace App\Modules\Dashboard\Tests\Feature;

use App\Models\User;
use App\Models\Tax;
use App\Modules\Partner\Models\Partner;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $jwtToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->jwtToken = $this->generateJwt($this->user);
    }

    /**
     * Generate a valid JWT token for test requests (mirrors AuthController logic).
     */
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

    /**
     * Helper: make an authenticated GET request with the JWT cookie.
     */
    private function authGet(string $uri)
    {
        return $this->withCookie('token', $this->jwtToken)->get($uri);
    }

    public function test_dashboard_returns_200_with_expected_view_data(): void
    {
        $response = $this->authGet('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.index');
        $response->assertViewHasAll([
            'month', 'year', 'kpis',
            'revenueByDay', 'revenueByWeek', 'revenueByMonth',
            'topSoldByQty', 'topSoldByRevenue',
            'topPurchasedProducts', 'topCategories', 'topCustomers',
            'amountsByClient', 'amountsBySupplier', 'recentSales',
        ]);
    }

    public function test_dashboard_defaults_to_current_month_year(): void
    {
        $response = $this->authGet('/dashboard');

        $response->assertViewHas('month', now()->month);
        $response->assertViewHas('year', now()->year);
    }

    public function test_dashboard_accepts_custom_month_year_filter(): void
    {
        $response = $this->authGet('/dashboard?month=3&year=2026');

        $response->assertStatus(200);
        $response->assertViewHas('month', 3);
        $response->assertViewHas('year', 2026);
    }

    public function test_dashboard_kpis_reflect_filtered_period(): void
    {
        $tax = Tax::create(['name' => 'IGV', 'code' => 'IGV18', 'rate' => 18]);
        $category = Category::create(['name' => 'Test']);
        $product = Product::create([
            'category_id' => $category->id, 'tax_id' => $tax->id,
            'code' => 'T01', 'name' => 'Test Product', 'cost' => 10, 'price' => 20, 'stock' => 50,
        ]);
        $client = Partner::create([
            'type' => 'cliente', 'business_name' => 'Test Client', 'document_number' => '12345678901',
        ]);

        // Sale in March 2026
        Sale::create([
            'partner_id' => $client->id, 'user_id' => $this->user->id, 'tax_id' => $tax->id,
            'number' => 'F001-0001', 'issue_date' => '2026-03-15',
            'status' => Sale::STATUS_APPROVED, 'total' => 500, 'subtotal' => 423.73,
            'tax_amount' => 76.27, 'paid_amount' => 0, 'pending_balance' => 500,
        ]);

        // Sale in June 2026 — should NOT appear in March filter
        Sale::create([
            'partner_id' => $client->id, 'user_id' => $this->user->id, 'tax_id' => $tax->id,
            'number' => 'F001-0002', 'issue_date' => '2026-06-15',
            'status' => Sale::STATUS_APPROVED, 'total' => 999, 'subtotal' => 847.46,
            'tax_amount' => 151.54, 'paid_amount' => 0, 'pending_balance' => 999,
        ]);

        $response = $this->authGet('/dashboard?month=3&year=2026');

        $response->assertStatus(200);
        $kpis = $response->viewData('kpis');
        $this->assertEquals(500, $kpis['total_revenue']);
        $this->assertEquals(1, $kpis['transaction_count']);
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect();
    }
}
