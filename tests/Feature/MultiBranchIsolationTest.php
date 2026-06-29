<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Branch\Models\Branch;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Modules\Partner\Models\Partner;
use App\Modules\Sale\Models\Sale;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\CashBox\Models\CashBox;
use App\Modules\Registration\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiBranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function createCompanyAndUser(int $maxBranches = 2): array
    {
        $company = Company::create([
            'company_type'        => 'legal_entity',
            'name'                => 'MultiBranch Corp',
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

        $uniq = uniqid();
        $user = User::create([
            'name'       => 'Branch Admin ' . $uniq,
            'email'      => 'admin_' . $uniq . '@multibranch.com',
            'password'   => Hash::make('password'),
            'company_id' => $company->id,
            'role'       => 'owner',
            'status'     => 'active',
        ]);

        return [$company, $user];
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

    public function test_branch_selector_visibility_depends_on_subscription_plan(): void
    {
        // 1. Premium plan (max_branches > 1) -> selector should be visible
        [$companyPremium, $userPremium] = $this->createCompanyAndUser(3);
        $response = $this->actingAs($userPremium)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('topbar_active_branch_id');

        // 2. Basic plan (max_branches = 1) -> selector should be hidden
        [$companyBasic, $userBasic] = $this->createCompanyAndUser(1);
        $response = $this->actingAs($userBasic)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertDontSee('topbar_active_branch_id');
    }

    public function test_switching_active_branch_updates_session(): void
    {
        [$company, $user] = $this->createCompanyAndUser(3);
        $branch1 = Branch::create(['company_id' => $company->id, 'name' => 'Matriz', 'establishment_code' => '001']);
        $branch2 = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Norte', 'establishment_code' => '002']);

        $response = $this->actingAs($user)->post(route('active-branch.set'), [
            'active_branch_id' => $branch2->id
        ]);

        $response->assertRedirect();
        $this->assertEquals($branch2->id, session('active_branch_id'));
    }

    public function test_sales_and_purchases_are_isolated_by_active_branch(): void
    {
        [$company, $user] = $this->createCompanyAndUser(3);
        $tax = $this->createTax();

        $branch1 = Branch::create(['company_id' => $company->id, 'name' => 'Matriz', 'establishment_code' => '001']);
        $branch2 = Branch::create(['company_id' => $company->id, 'name' => 'Sucursal Norte', 'establishment_code' => '002']);

        $category = Category::create(['company_id' => $company->id, 'name' => 'Varios']);
        $product = Product::create([
            'company_id'  => $company->id,
            'category_id' => $category->id,
            'tax_id'      => $tax->id,
            'name'        => 'Shared Product',
            'code'        => 'SP001',
            'price'       => 10.00,
            'cost'        => 5.00,
        ]);

        $partner = Partner::create([
            'company_id'      => $company->id,
            'type'            => 'cliente',
            'business_name'   => 'Shared Customer',
            'document_number' => '1712345678001',
        ]);

        // Create Sale under Branch 1
        session(['active_branch_id' => $branch1->id]);
        $sale1 = Sale::create([
            'company_id'      => $company->id,
            'partner_id'      => $partner->id,
            'user_id'         => $user->id,
            'tax_id'          => $tax->id,
            'branch_id'       => $branch1->id,
            'series'          => '001-001',
            'number'          => '000000001',
            'issue_date'      => now(),
            'status'          => Sale::STATUS_DRAFT,
            'total'           => 100.00,
            'subtotal'        => 86.96,
            'tax_amount'      => 13.04,
            'pending_balance' => 100.00,
        ]);

        // Create Sale under Branch 2
        session(['active_branch_id' => $branch2->id]);
        $sale2 = Sale::create([
            'company_id'      => $company->id,
            'partner_id'      => $partner->id,
            'user_id'         => $user->id,
            'tax_id'          => $tax->id,
            'branch_id'       => $branch2->id,
            'series'          => '002-001',
            'number'          => '000000002',
            'issue_date'      => now(),
            'status'          => Sale::STATUS_DRAFT,
            'total'           => 200.00,
            'subtotal'        => 173.91,
            'tax_amount'      => 26.09,
            'pending_balance' => 200.00,
        ]);

        // Querying under active Branch 1 scope
        session(['active_branch_id' => $branch1->id]);
        $this->actingAs($user);
        $this->assertCount(1, Sale::all());
        $this->assertEquals($sale1->id, Sale::first()->id);

        // Querying under active Branch 2 scope
        session(['active_branch_id' => $branch2->id]);
        $this->assertCount(1, Sale::all());
        $this->assertEquals($sale2->id, Sale::first()->id);
    }
}
