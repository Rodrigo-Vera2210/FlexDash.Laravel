<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Branch\Models\Branch;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Registration\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InventoryTransferTest extends TestCase
{
    use RefreshDatabase;

    private function createCompanyAndUser(int $maxBranches = 2): array
    {
        $company = Company::create([
            'company_type'        => 'legal_entity',
            'name'                => 'Inventory Corp',
            'legal_entity_flag'   => true,
            'natural_entity_flag' => false,
            'subscription_plan'   => 'standard',
            'subscription_status' => 'active',
            'max_branches'        => $maxBranches,
            'city'                => 'Quito',
            'state_province'      => 'Pichincha',
            'postal_code'         => '170150',
            'country'             => 'Ecuador',
        ]);

        $user = User::create([
            'name'       => 'Inventory Owner',
            'email'      => 'owner@inventory.com',
            'password'   => Hash::make('password'),
            'company_id' => $company->id,
            'role'       => 'owner',
            'status'     => 'active',
        ]);

        return [$company, $user];
    }

    private function createCategory(Company $company): Category
    {
        return Category::create([
            'name'       => 'Test Category',
            'company_id' => $company->id,
            'is_active'  => true,
        ]);
    }

    private function createTax(): \App\Models\Tax
    {
        return \App\Models\Tax::create([
            'name'      => 'IVA 15%',
            'code'      => 'IVA15',
            'rate'      => 15.00,
            'is_active' => true,
        ]);
    }

    public function test_inventory_stock_view_lists_products_and_stocks(): void
    {
        [$company, $user] = $this->createCompanyAndUser();
        $category = $this->createCategory($company);
        $tax = $this->createTax();

        $branch = Branch::create([
            'company_id'         => $company->id,
            'name'               => 'Test Branch 1',
            'establishment_code' => '001',
            'is_active'          => true,
        ]);
        
        $product = Product::create([
            'company_id'  => $company->id,
            'category_id' => $category->id,
            'tax_id'      => $tax->id,
            'name'        => 'Test Product',
            'code'        => 'P001',
            'price'       => 10.00,
            'cost'        => 5.00,
            'is_active'   => true,
        ]);

        $branch->products()->attach($product->id, ['stock' => 50]);

        $response = $this->actingAs($user)->get(route('inventory.stock'));

        $response->assertStatus(200);
        $response->assertSee('Test Product');
        $response->assertSee('50');
    }

    public function test_stock_transfer_succeeds_and_updates_stocks_and_kardex(): void
    {
        [$company, $user] = $this->createCompanyAndUser(3);
        $category = $this->createCategory($company);
        $tax = $this->createTax();
        
        $branch1 = Branch::create([
            'company_id'         => $company->id,
            'name'               => 'Origin Branch',
            'establishment_code' => '001',
            'is_active'          => true,
        ]);

        $branch2 = Branch::create([
            'company_id'         => $company->id,
            'name'               => 'Destination Branch',
            'establishment_code' => '002',
            'is_active'          => true,
        ]);

        $product = Product::create([
            'company_id'  => $company->id,
            'category_id' => $category->id,
            'tax_id'      => $tax->id,
            'name'        => 'Transfer Product',
            'code'        => 'P002',
            'price'       => 15.00,
            'cost'        => 8.00,
            'is_active'   => true,
        ]);

        $branch1->products()->attach($product->id, ['stock' => 30]);
        $branch2->products()->attach($product->id, ['stock' => 10]);

        $response = $this->actingAs($user)->post(route('inventory.transfers.store'), [
            'origin_branch_id'      => $branch1->id,
            'destination_branch_id' => $branch2->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10]
            ]
        ]);

        $response->assertRedirect(route('inventory.transfers.index'));

        // Assert stocks are updated
        $this->assertDatabaseHas('branch_product', [
            'branch_id'  => $branch1->id,
            'product_id' => $product->id,
            'stock'      => 20,
        ]);

        $this->assertDatabaseHas('branch_product', [
            'branch_id'  => $branch2->id,
            'product_id' => $product->id,
            'stock'      => 20,
        ]);

        // Assert Kardex entries
        $this->assertDatabaseHas('inventory_movements', [
            'branch_id'    => $branch1->id,
            'product_id'   => $product->id,
            'type'         => 'egreso_traslado',
            'quantity'     => 10,
            'stock_before' => 30,
            'stock_after'  => 20,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'branch_id'    => $branch2->id,
            'product_id'   => $product->id,
            'type'         => 'ingreso_traslado',
            'quantity'     => 10,
            'stock_before' => 10,
            'stock_after'  => 20,
        ]);
    }

    public function test_stock_transfer_fails_if_insufficient_stock(): void
    {
        [$company, $user] = $this->createCompanyAndUser(2);
        $category = $this->createCategory($company);
        $tax = $this->createTax();

        $branch1 = Branch::create([
            'company_id'         => $company->id,
            'name'               => 'Origin Branch',
            'establishment_code' => '001',
            'is_active'          => true,
        ]);

        $branch2 = Branch::create([
            'company_id'         => $company->id,
            'name'               => 'Destination Branch',
            'establishment_code' => '002',
            'is_active'          => true,
        ]);

        $product = Product::create([
            'company_id'  => $company->id,
            'category_id' => $category->id,
            'tax_id'      => $tax->id,
            'name'        => 'Product A',
            'code'        => 'P003',
            'price'       => 12.00,
            'cost'        => 6.00,
            'is_active'   => true,
        ]);

        $branch1->products()->attach($product->id, ['stock' => 5]);

        $response = $this->actingAs($user)->post(route('inventory.transfers.store'), [
            'origin_branch_id'      => $branch1->id,
            'destination_branch_id' => $branch2->id,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10] // Exceeds stock
            ]
        ]);

        $response->assertSessionHasErrors('form');
        
        // Assert stocks are unmodified
        $this->assertDatabaseHas('branch_product', [
            'branch_id'  => $branch1->id,
            'product_id' => $product->id,
            'stock'      => 5,
        ]);
    }

    public function test_stock_transfer_fails_if_basic_plan_and_max_branches_is_one(): void
    {
        [$company, $user] = $this->createCompanyAndUser(1); // Only 1 branch

        $response = $this->actingAs($user)->get(route('inventory.transfers.create'));

        $response->assertStatus(403);
    }
}
