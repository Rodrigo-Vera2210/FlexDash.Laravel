<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Branch\Models\Branch;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Modules\Registration\Models\Company;
use App\Modules\Sale\Models\Sale;
use App\Modules\Sale\Models\SaleDetail;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BranchInventoryTest extends TestCase
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

        $base64url = fn ($data) => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
        $signingInput = $base64url($header) . '.' . $base64url($payload);
        $signature = hash_hmac('sha256', $signingInput, $secret, true);

        return $base64url($header) . '.' . $base64url($payload) . '.' . $base64url($signature);
    }

    private function setupCompanyWithBranches(): array
    {
        $company = Company::create([
            'company_type'        => 'legal_entity',
            'name'                => 'Inventory Branch Corp',
            'legal_entity_flag'   => true,
            'natural_entity_flag' => false,
            'subscription_plan'   => 'standard',
            'subscription_status' => 'active',
            'city'                => 'Quito',
            'state_province'      => 'Pichincha',
            'postal_code'         => '170150',
            'country'             => 'Ecuador',
        ]);

        $branchA = Branch::create([
            'company_id'         => $company->id,
            'name'               => 'Matriz',
            'establishment_code' => '001',
            'is_active'          => true,
        ]);

        $branchB = Branch::create([
            'company_id'         => $company->id,
            'name'               => 'Norte',
            'establishment_code' => '002',
            'is_active'          => true,
        ]);

        $user = User::create([
            'name'              => 'Inv Admin',
            'email'             => 'invadmin@test.com',
            'password'          => Hash::make('password'),
            'company_id'        => $company->id,
            'branch_id'         => $branchA->id,
            'role'              => 'owner',
            'status'            => 'active',
        ]);
        $user->email_verified_at = now();
        $user->save();

        $this->actingAs($user);

        $tax = \App\Models\Tax::create([
            'name'      => 'IVA 15%',
            'code'      => 'IVA15B',
            'rate'      => 15.00,
            'is_active' => true,
        ]);

        $category = Category::create(['name' => 'Cat Inv', 'is_active' => true]);

        $product = Product::create([
            'category_id'   => $category->id,
            'tax_id'        => $tax->id,
            'code'          => 'INV-001',
            'name'          => 'Producto Inventario',
            'unit'          => 'UND',
            'cost'          => 5,
            'price'         => 10,
            'minimum_stock' => 1,
            'stock'         => 0,
        ]);

        $product->branches()->attach([
            $branchA->id => ['stock' => 0],
            $branchB->id => ['stock' => 0],
        ]);

        return [$company, $user, $branchA, $branchB, $product];
    }

    public function test_stock_decrements_only_at_assigned_branch(): void
    {
        [, $user, $branchA, $branchB, $product] = $this->setupCompanyWithBranches();
        $inventory = app(InventoryService::class);

        $inventory->entry($product, 100, 5, 'manual', 1, 'Stock inicial A', $branchA->id);
        $inventory->entry($product, 50, 5, 'manual', 2, 'Stock inicial B', $branchB->id);

        $partner = \App\Modules\Partner\Models\Partner::create([
            'business_name'   => 'Cliente Test',
            'document_number' => '1234567890',
            'type'            => 'cliente',
            'is_active'       => true,
        ]);

        $sale = Sale::create([
            'company_id'      => $user->company_id,
            'branch_id'       => $branchA->id,
            'partner_id'      => $partner->id,
            'user_id'         => $user->id,
            'number'          => 'F001-00000001',
            'series'          => 'F001',
            'issue_date'      => now()->toDateString(),
            'status'          => Sale::STATUS_DRAFT,
            'subtotal'        => 20,
            'tax_amount'      => 0,
            'total'           => 20,
            'paid_amount'     => 0,
            'pending_balance' => 20,
        ]);

        SaleDetail::create([
            'sale_id'    => $sale->id,
            'product_id' => $product->id,
            'quantity'   => 10,
            'unit_price' => 2,
            'cost_price' => 5,
            'discount'   => 0,
            'subtotal'   => 20,
        ]);

        app(SaleService::class)->approve($sale);

        $product->refresh()->load('branches');
        $stockA = (float) $product->branches->firstWhere('id', $branchA->id)->pivot->stock;
        $stockB = (float) $product->branches->firstWhere('id', $branchB->id)->pivot->stock;

        $this->assertEquals(90.0, $stockA);
        $this->assertEquals(50.0, $stockB);
        $this->assertEquals(140.0, (float) $product->fresh()->stock);
    }

    public function test_general_inventory_shows_branch_columns(): void
    {
        [, $user, $branchA, $branchB, $product] = $this->setupCompanyWithBranches();
        app(InventoryService::class)->entry($product, 25, 5, 'manual', 1, null, $branchA->id);
        app(InventoryService::class)->entry($product, 15, 5, 'manual', 2, null, $branchB->id);

        $token = $this->generateJwtForUser($user);
        $response = $this->withCookie('token', $token)->get('/products');

        $response->assertStatus(200);
        $response->assertSee('Matriz');
        $response->assertSee('Norte');
        $response->assertSee('Stock Total');
        $response->assertSee('25.00');
        $response->assertSee('15.00');
        $response->assertSee('40.00');
    }

    public function test_total_stock_is_sum_of_all_branches(): void
    {
        [, , $branchA, $branchB, $product] = $this->setupCompanyWithBranches();
        $inventory = app(InventoryService::class);

        $inventory->entry($product, 30, 5, 'manual', 1, null, $branchA->id);
        $inventory->entry($product, 20, 5, 'manual', 2, null, $branchB->id);

        $product->load('branches');

        $this->assertEquals(50.0, $product->total_stock);
    }
}
