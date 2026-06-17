<?php

namespace App\Modules\Dashboard\Tests\Unit;

use App\Modules\Dashboard\Services\DashboardService;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleDetail;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\PurchaseDetail;
use App\Modules\Product\Models\Product;
use App\Modules\Product\Models\Category;
use App\Modules\Partner\Models\Partner;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;
    private User $user;
    private Tax $tax;
    private Category $categoryA;
    private Category $categoryB;
    private Product $productA;
    private Product $productB;
    private Partner $client;
    private Partner $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new DashboardService();

        // ── Seed base data ──────────────────────────────────────────
        $this->user = User::factory()->create();

        $this->tax = Tax::create([
            'name' => 'IGV', 'code' => 'IGV18', 'rate' => 18.00,
        ]);

        $this->categoryA = Category::create(['name' => 'Electrónica']);
        $this->categoryB = Category::create(['name' => 'Alimentos']);

        $this->productA = Product::create([
            'category_id' => $this->categoryA->id,
            'tax_id'      => $this->tax->id,
            'code'        => 'PROD-A',
            'name'        => 'Laptop',
            'cost'        => 500,
            'price'       => 800,
            'stock'       => 20,
        ]);

        $this->productB = Product::create([
            'category_id' => $this->categoryB->id,
            'tax_id'      => $this->tax->id,
            'code'        => 'PROD-B',
            'name'        => 'Arroz 1kg',
            'cost'        => 2,
            'price'       => 3.50,
            'stock'       => 100,
        ]);

        $this->client = Partner::create([
            'type'            => 'cliente',
            'business_name'   => 'Acme Corp',
            'document_number' => '20123456789',
        ]);

        $this->supplier = Partner::create([
            'type'            => 'proveedor',
            'business_name'   => 'Distribuidora XYZ',
            'document_number' => '20987654321',
        ]);
    }

    // ── Helper to create a sale with details ─────────────────────────
    private function createSale(array $overrides = [], array $details = []): Sale
    {
        $sale = Sale::create(array_merge([
            'partner_id' => $this->client->id,
            'user_id'    => $this->user->id,
            'tax_id'     => $this->tax->id,
            'number'     => 'F001-' . str_pad(Sale::count() + 1, 4, '0', STR_PAD_LEFT),
            'issue_date' => '2026-06-15',
            'status'     => Sale::STATUS_APPROVED,
            'total'      => 100,
            'subtotal'   => 84.75,
            'tax_amount' => 15.25,
            'paid_amount'      => 0,
            'pending_balance'  => 100,
        ], $overrides));

        foreach ($details as $detail) {
            SaleDetail::create(array_merge([
                'sale_id' => $sale->id,
            ], $detail));
        }

        return $sale;
    }

    private function createPurchase(array $overrides = [], array $details = []): Purchase
    {
        $purchase = Purchase::create(array_merge([
            'partner_id' => $this->supplier->id,
            'user_id'    => $this->user->id,
            'tax_id'     => $this->tax->id,
            'number'     => 'C001-' . str_pad(Purchase::count() + 1, 4, '0', STR_PAD_LEFT),
            'issue_date' => '2026-06-15',
            'status'     => Purchase::STATUS_APPROVED,
            'total'      => 50,
            'subtotal'   => 42.37,
            'tax_amount' => 7.63,
            'paid_amount'      => 0,
            'pending_balance'  => 50,
        ], $overrides));

        foreach ($details as $detail) {
            PurchaseDetail::create(array_merge([
                'purchase_id' => $purchase->id,
            ], $detail));
        }

        return $purchase;
    }

    // ═══════════════════════════════════════════════════════════════════
    // getKpiSummary() tests
    // ═══════════════════════════════════════════════════════════════════

    public function test_kpi_summary_returns_correct_revenue_and_count(): void
    {
        $this->createSale(['total' => 200, 'issue_date' => '2026-06-10']);
        $this->createSale(['total' => 300, 'issue_date' => '2026-06-20']);
        // Cancelled sale — should be excluded
        $this->createSale(['total' => 999, 'issue_date' => '2026-06-15', 'status' => Sale::STATUS_CANCELLED]);
        // Out-of-period sale — should be excluded
        $this->createSale(['total' => 500, 'issue_date' => '2026-05-10']);

        $kpis = $this->service->getKpiSummary(6, 2026);

        $this->assertEquals(500, $kpis['total_revenue']);
        $this->assertEquals(2, $kpis['transaction_count']);
        $this->assertEquals(250, $kpis['average_ticket']);
    }

    public function test_kpi_summary_calculates_estimated_profit(): void
    {
        $sale = $this->createSale(['total' => 800, 'issue_date' => '2026-06-10'], [
            ['product_id' => $this->productA->id, 'quantity' => 1, 'unit_price' => 800, 'cost_price' => 500, 'subtotal' => 800],
        ]);

        $kpis = $this->service->getKpiSummary(6, 2026);

        // Profit: (800 - 500) * 1 = 300
        $this->assertEquals(300, $kpis['estimated_profit']);
    }

    public function test_kpi_summary_includes_accounts_receivable_and_payable(): void
    {
        $this->createSale(['pending_balance' => 150, 'status' => Sale::STATUS_APPROVED, 'issue_date' => '2026-06-10']);
        $this->createPurchase(['pending_balance' => 75, 'status' => Purchase::STATUS_APPROVED, 'issue_date' => '2026-06-10']);

        $kpis = $this->service->getKpiSummary(6, 2026);

        $this->assertEquals(150, $kpis['accounts_receivable']);
        $this->assertEquals(75, $kpis['accounts_payable']);
    }

    public function test_kpi_summary_returns_zeros_when_no_data(): void
    {
        $kpis = $this->service->getKpiSummary(1, 2020);

        $this->assertEquals(0, $kpis['total_revenue']);
        $this->assertEquals(0, $kpis['transaction_count']);
        $this->assertEquals(0, $kpis['average_ticket']);
        $this->assertEquals(0, $kpis['estimated_profit']);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Revenue aggregation tests
    // ═══════════════════════════════════════════════════════════════════

    public function test_revenue_by_day_groups_correctly(): void
    {
        $this->createSale(['total' => 100, 'issue_date' => '2026-06-10']);
        $this->createSale(['total' => 200, 'issue_date' => '2026-06-10']);
        $this->createSale(['total' => 50,  'issue_date' => '2026-06-15']);

        $result = $this->service->getRevenueByDay(6, 2026);

        $this->assertCount(2, $result);
        $this->assertEquals(300, $result->where('fecha', '2026-06-10')->first()->total);
        $this->assertEquals(50,  $result->where('fecha', '2026-06-15')->first()->total);
    }

    public function test_revenue_by_day_excludes_cancelled(): void
    {
        $this->createSale(['total' => 100, 'issue_date' => '2026-06-10']);
        $this->createSale(['total' => 999, 'issue_date' => '2026-06-10', 'status' => Sale::STATUS_CANCELLED]);

        $result = $this->service->getRevenueByDay(6, 2026);

        $this->assertEquals(100, $result->first()->total);
    }

    public function test_revenue_by_month_groups_by_month_number(): void
    {
        $this->createSale(['total' => 100, 'issue_date' => '2026-01-15']);
        $this->createSale(['total' => 200, 'issue_date' => '2026-06-15']);
        $this->createSale(['total' => 300, 'issue_date' => '2026-06-20']);

        $result = $this->service->getRevenueByMonth(2026);

        $this->assertCount(2, $result);
        $this->assertEquals(100, $result->where('mes', 1)->first()->total);
        $this->assertEquals(500, $result->where('mes', 6)->first()->total);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Top sold products tests
    // ═══════════════════════════════════════════════════════════════════

    public function test_top_sold_by_quantity_ranks_correctly(): void
    {
        $sale = $this->createSale(['issue_date' => '2026-06-10'], [
            ['product_id' => $this->productA->id, 'quantity' => 5,  'unit_price' => 800, 'cost_price' => 500, 'subtotal' => 4000],
            ['product_id' => $this->productB->id, 'quantity' => 20, 'unit_price' => 3.5, 'cost_price' => 2,   'subtotal' => 70],
        ]);

        $result = $this->service->getTopSoldProductsByQuantity(6, 2026);

        $this->assertEquals('Arroz 1kg', $result->first()->name); // 20 units > 5 units
        $this->assertEquals(20, $result->first()->total_quantity);
    }

    public function test_top_sold_by_revenue_ranks_correctly(): void
    {
        $sale = $this->createSale(['issue_date' => '2026-06-10'], [
            ['product_id' => $this->productA->id, 'quantity' => 5,  'unit_price' => 800, 'cost_price' => 500, 'subtotal' => 4000],
            ['product_id' => $this->productB->id, 'quantity' => 20, 'unit_price' => 3.5, 'cost_price' => 2,   'subtotal' => 70],
        ]);

        $result = $this->service->getTopSoldProductsByRevenue(6, 2026);

        $this->assertEquals('Laptop', $result->first()->name); // $4000 > $70
        $this->assertEquals(4000, $result->first()->total_revenue);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Top purchased products tests
    // ═══════════════════════════════════════════════════════════════════

    public function test_top_purchased_products_ranks_by_quantity(): void
    {
        $this->createPurchase(['issue_date' => '2026-06-10'], [
            ['product_id' => $this->productA->id, 'quantity' => 3,  'unit_cost' => 500, 'subtotal' => 1500],
            ['product_id' => $this->productB->id, 'quantity' => 50, 'unit_cost' => 2,   'subtotal' => 100],
        ]);

        $result = $this->service->getTopPurchasedProducts(6, 2026);

        $this->assertEquals('Arroz 1kg', $result->first()->name);
        $this->assertEquals(50, $result->first()->total_quantity);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Top selling categories tests
    // ═══════════════════════════════════════════════════════════════════

    public function test_top_categories_with_percentages(): void
    {
        $this->createSale(['issue_date' => '2026-06-10'], [
            ['product_id' => $this->productA->id, 'quantity' => 1, 'unit_price' => 800, 'cost_price' => 500, 'subtotal' => 800],
            ['product_id' => $this->productB->id, 'quantity' => 10, 'unit_price' => 3.5, 'cost_price' => 2, 'subtotal' => 200],
        ]);

        $result = $this->service->getTopSellingCategories(6, 2026);

        $this->assertCount(2, $result);
        $first = $result->first();
        $this->assertEquals('Electrónica', $first->name);
        $this->assertEquals(80.0, $first->percentage); // 800/1000 = 80%
    }

    // ═══════════════════════════════════════════════════════════════════
    // Frequent customers tests
    // ═══════════════════════════════════════════════════════════════════

    public function test_frequent_customers_ranked_by_transaction_count(): void
    {
        // Client with 3 sales
        $this->createSale(['issue_date' => '2026-06-01', 'total' => 100]);
        $this->createSale(['issue_date' => '2026-06-05', 'total' => 200]);
        $this->createSale(['issue_date' => '2026-06-10', 'total' => 150]);

        $result = $this->service->getTopFrequentCustomers(6, 2026);

        $this->assertCount(1, $result);
        $this->assertEquals('Acme Corp', $result->first()->display_name);
        $this->assertEquals(3, $result->first()->transaction_count);
        $this->assertEquals(450, $result->first()->total_amount);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Amounts by client/supplier tests
    // ═══════════════════════════════════════════════════════════════════

    public function test_amounts_by_client_aggregates_correctly(): void
    {
        $this->createSale([
            'issue_date'      => '2026-06-10',
            'total'           => 500,
            'paid_amount'     => 200,
            'pending_balance' => 300,
        ]);
        $this->createSale([
            'issue_date'      => '2026-06-15',
            'total'           => 300,
            'paid_amount'     => 300,
            'pending_balance' => 0,
        ]);

        $result = $this->service->getAmountsByClient(6, 2026);

        $this->assertCount(1, $result);
        $this->assertEquals(800, $result->first()->total_amount);
        $this->assertEquals(500, $result->first()->paid_amount);
        $this->assertEquals(300, $result->first()->pending_balance);
    }

    public function test_amounts_by_supplier_aggregates_correctly(): void
    {
        $this->createPurchase([
            'issue_date'      => '2026-06-10',
            'total'           => 1000,
            'paid_amount'     => 400,
            'pending_balance' => 600,
        ]);

        $result = $this->service->getAmountsBySupplier(6, 2026);

        $this->assertCount(1, $result);
        $this->assertEquals(1000, $result->first()->total_amount);
        $this->assertEquals(400,  $result->first()->paid_amount);
        $this->assertEquals(600,  $result->first()->pending_balance);
    }

    // ═══════════════════════════════════════════════════════════════════
    // Recent sales tests
    // ═══════════════════════════════════════════════════════════════════

    public function test_recent_sales_returns_latest_with_relations(): void
    {
        $this->createSale(['issue_date' => '2026-06-01']);
        $this->createSale(['issue_date' => '2026-06-10']);
        $this->createSale(['issue_date' => '2026-06-15']);

        $result = $this->service->getRecentSales(2);

        $this->assertCount(2, $result);
        $this->assertTrue($result->first()->relationLoaded('partner'));
        $this->assertTrue($result->first()->relationLoaded('details'));
    }

    // ═══════════════════════════════════════════════════════════════════
    // Period filter edge cases
    // ═══════════════════════════════════════════════════════════════════

    public function test_period_filter_excludes_other_months(): void
    {
        $this->createSale(['total' => 100, 'issue_date' => '2026-06-15']);
        $this->createSale(['total' => 999, 'issue_date' => '2026-07-01']);

        $result = $this->service->getRevenueByDay(6, 2026);

        $this->assertCount(1, $result);
        $this->assertEquals(100, $result->first()->total);
    }
}
