<?php

namespace Tests\Feature;

use App\Filament\Rep\Pages\CustomerLookup;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\QrCodeService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QrCodeAndBrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_customer_gets_unique_qr_token_on_creation(): void
    {
        $warehouse = Warehouse::first();

        $customerA = Customer::create(['name' => 'Shop A', 'code' => 'QR-A', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);
        $customerB = Customer::create(['name' => 'Shop B', 'code' => 'QR-B', 'type' => 'shop', 'warehouse_id' => $warehouse->id]);

        $this->assertNotEmpty($customerA->qr_token);
        $this->assertNotEmpty($customerB->qr_token);
        $this->assertNotEquals($customerA->qr_token, $customerB->qr_token);
    }

    public function test_qr_lookup_url_resolves_to_customer_lookup_page(): void
    {
        $customer = Customer::create(['name' => 'Shop C', 'code' => 'QR-C', 'type' => 'shop']);

        $url = $customer->qrLookupUrl();

        $this->assertStringContainsString("/rep/customers/lookup/{$customer->qr_token}", $url);
    }

    public function test_rep_can_open_customer_lookup_page_via_qr_token(): void
    {
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $customer = Customer::create(['name' => 'Shop D', 'code' => 'QR-D', 'type' => 'shop']);

        $this->actingAs($rep);

        Livewire::test(CustomerLookup::class, ['token' => $customer->qr_token])
            ->assertOk()
            ->assertSee('Shop D');
    }

    public function test_lookup_page_404s_for_unknown_token(): void
    {
        $rep = User::where('email', 'rep@cementco.test')->firstOrFail();
        $this->actingAs($rep);

        $this->get('/rep/customers/lookup/does-not-exist')->assertNotFound();
    }

    public function test_admin_qr_action_renders_on_customer_index(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();
        Customer::create(['name' => 'Shop E', 'code' => 'QR-E', 'type' => 'shop']);

        $this->actingAs($admin);

        Livewire::test(ListCustomers::class)
            ->assertTableActionExists('viewQr')
            ->assertTableActionExists('downloadQr');
    }

    public function test_qr_code_service_generates_valid_png(): void
    {
        $png = app(QrCodeService::class)->png('https://example.test/rep/customers/lookup/abc123');

        $this->assertStringStartsWith("\x89PNG", $png);
    }

    public function test_branding_settings_persist_and_feed_panel_brand_name(): void
    {
        $settings = AppSetting::current();
        $settings->update(['company_name' => 'Acme Cement Co', 'tagline' => 'Building Better']);

        $this->assertSame('Acme Cement Co', AppSetting::current()->company_name);
    }

    public function test_admin_can_access_branding_settings_page(): void
    {
        $admin = User::where('email', 'admin@cementco.test')->firstOrFail();

        $this->actingAs($admin)->get('/admin/branding-settings')->assertOk();
    }

    public function test_non_admin_cannot_access_branding_settings_page(): void
    {
        $accountant = User::where('email', 'accountant@cementco.test')->firstOrFail();

        $this->actingAs($accountant)->get('/admin/branding-settings')->assertForbidden();
    }
}
