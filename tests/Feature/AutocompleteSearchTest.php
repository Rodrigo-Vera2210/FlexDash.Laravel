<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Branch\Models\Branch;
use App\Modules\Product\Models\Category;
use App\Modules\Product\Models\Product;
use App\Modules\Partner\Models\Partner;
use App\Modules\Sale\Models\Sale;
use App\Modules\Registration\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AutocompleteSearchTest extends TestCase
{
    use RefreshDatabase;

    private function createCompanyAndUser(): array
    {
        $company = Company::create([
            'company_type'        => 'legal_entity',
            'name'                => 'Search Corp',
            'legal_entity_flag'   => true,
            'natural_entity_flag' => false,
            'subscription_plan'   => 'standard',
            'subscription_status' => 'active',
            'max_branches'        => 3,
            'city'                => 'Quito',
            'state_province'      => 'Pichincha',
            'postal_code'         => '170150',
            'country'             => 'Ecuador',
        ]);

        $user = User::create([
            'name'       => 'Search Operator',
            'email'      => 'search@corp.com',
            'password'   => Hash::make('password'),
            'company_id' => $company->id,
            'role'       => 'owner',
            'status'     => 'active',
        ]);

        return [$company, $user];
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $response = $this->getJson(route('search.partners', ['q' => 'test']));
        $response->assertStatus(401);
    }

    public function test_partners_autocomplete_scopes_and_filters(): void
    {
        [$company, $user] = $this->createCompanyAndUser();

        // Create partners
        $client1 = Partner::create([
            'company_id' => $company->id,
            'type' => 'cliente',
            'business_name' => 'Acme Corporation',
            'document_number' => '1712345678001',
        ]);

        $supplier = Partner::create([
            'company_id' => $company->id,
            'type' => 'proveedor',
            'business_name' => 'Acme Supplies',
            'document_number' => '1712345678002',
        ]);

        // Query partners
        $response = $this->actingAs($user)->getJson(route('search.partners', ['q' => 'Acme']));
        $response->assertStatus(200);
        $response->assertJsonCount(2);

        // Filter by type
        $response = $this->actingAs($user)->getJson(route('search.partners', ['q' => 'Acme', 'type' => 'cliente']));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $client1->id);
    }

    public function test_products_autocomplete_scopes_and_filters(): void
    {
        [$company, $user] = $this->createCompanyAndUser();

        $tax = \App\Models\Tax::create([
            'company_id' => $company->id,
            'name' => 'IVA 15%',
            'code' => 'IVA15',
            'rate' => 15.00,
            'is_active' => true,
        ]);

        $category = Category::create(['company_id' => $company->id, 'name' => 'General']);
        
        $product = Product::create([
            'company_id'  => $company->id,
            'category_id' => $category->id,
            'tax_id'      => $tax->id,
            'name'        => 'Super Widget XL',
            'code'        => 'SW001',
            'price'       => 20.00,
            'cost'        => 10.00,
        ]);

        $response = $this->actingAs($user)->getJson(route('search.products', ['q' => 'Widget']));
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $product->id);
        $response->assertJsonPath('0.tax_rate', 15);
    }
}
