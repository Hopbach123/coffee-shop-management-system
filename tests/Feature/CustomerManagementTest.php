<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (['0001_01_01_000000_create_users_table.php', '2026_08_14_000001_create_customers_table.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['name' => 'Customer Name', 'phone' => '0901234567', 'email' => 'customer@example.test', 'birthday' => '2000-02-29'], $overrides);
    }

    public function test_only_admin_can_list_open_form_or_create_customer(): void
    {
        foreach (['/admin/customers', '/admin/customers/create'] as $url) {
            $this->get($url)->assertRedirect(route('login'));
        }
        $this->post('/admin/customers', $this->payload())->assertRedirect(route('login'));
        $staff = $this->admin();
        $staff->forceFill(['role' => 'staff'])->save();
        $this->actingAs($staff, 'web');
        foreach (['/admin/customers', '/admin/customers/create'] as $url) {
            $this->get($url)->assertForbidden();
        }
        $this->post('/admin/customers', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_listing_displays_nulls_status_and_escaped_data_without_loyalty_columns(): void
    {
        $this->actingAs($this->admin(), 'web')->get('/admin/customers')->assertOk()->assertSee('Chưa có khách hàng');
        Customer::create(['name' => '<script>alert(1)</script>']);
        Customer::forceCreate($this->payload(['status' => 'inactive']));
        $this->get('/admin/customers')->assertOk()->assertViewIs('admin.customers.index')
            ->assertSee('—')->assertSee('29/02/2000')->assertSee('Đang hoạt động')->assertSee('Ngừng hoạt động')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('Điểm tích lũy')->assertDontSee('Tổng chi tiêu');
        $this->get('/admin/customers?search=Missing')->assertOk()->assertSee('Không tìm thấy khách hàng');
    }

    public function test_search_matches_name_or_phone_and_excludes_deleted_rows_and_email_only_matches(): void
    {
        $this->actingAs($this->admin(), 'web');
        $named = Customer::create(['name' => 'Match Name']);
        $phoned = Customer::create(['phone' => '0901234567']);
        Customer::create(['name' => 'Match deleted'])->delete();
        Customer::create(['email' => 'match@example.test']);
        $this->get('/admin/customers?search=Match')->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->pluck('id')->all() === [$named->id]);
        $this->get('/admin/customers?search=0901')->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->pluck('id')->all() === [$phoned->id]);
        $this->get('/admin/customers?search='.urlencode("' OR 1=1 --"))->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->total() === 0);
    }

    public function test_pagination_retains_search_and_newest_first_sort(): void
    {
        $this->actingAs($this->admin(), 'web');
        $ids = [];
        for ($i = 0; $i < 16; $i++) {
            $ids[] = Customer::forceCreate(['name' => 'Match '.$i, 'created_at' => '2026-01-01 12:00:00'])->id;
        }
        $newest = Customer::forceCreate(['name' => 'Match newest', 'created_at' => '2026-02-01 12:00:00']);
        $this->get('/admin/customers?search=Match')->assertOk()->assertSee('search=Match&amp;page=2', false)
            ->assertViewHas('customers', fn ($customers) => $customers->total() === 17 && $customers->count() === 15 && $customers->first()->id === $newest->id);
        $this->get('/admin/customers?search=Match&page=2')->assertOk()
            ->assertViewHas('customers', fn ($customers) => $customers->pluck('id')->all() === [$ids[1], $ids[0]] && str_contains($customers->previousPageUrl(), 'search=Match'));
    }

    public function test_create_whitelists_fields_uses_active_default_and_has_no_cross_domain_queries(): void
    {
        $this->actingAs($this->admin(), 'web')->get('/admin/customers/create')->assertOk()
            ->assertSee('name="_token"', false)->assertDontSee('name="status"', false)->assertDontSee('name="points"', false);
        DB::enableQueryLog();
        $this->post('/admin/customers', $this->payload(['status' => 'inactive', 'points' => 999, 'total_spent' => 999,
            'id' => 9999, 'created_at' => '2000-01-01', 'updated_at' => '2000-01-01', 'deleted_at' => '2000-01-01']))
            ->assertRedirect(route('admin.customers.index'))->assertSessionHas('success', 'Đã thêm khách hàng.');
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(orders|reservations)\b/i', $query['query']);
        }
        DB::disableQueryLog();
        DB::flushQueryLog();
        $customer = Customer::firstOrFail();
        $this->assertSame('active', $customer->status);
        $this->assertSame('Customer Name', $customer->name);
        $this->assertSame('0901234567', $customer->phone);
        $this->assertSame('customer@example.test', $customer->email);
        $this->assertSame('2000-02-29', $customer->birthday->format('Y-m-d'));
        $this->assertNull($customer->deleted_at);
        $this->assertNotSame(9999, $customer->id);
        $this->assertNotSame('2000-01-01', $customer->created_at->format('Y-m-d'));
        $this->assertArrayNotHasKey('points', $customer->getAttributes());
        $this->assertArrayNotHasKey('total_spent', $customer->getAttributes());
        $this->get('/admin/customers')->assertOk()->assertSee('Đã thêm khách hàng.');
    }

    public function test_any_single_identity_field_is_sufficient_and_multiple_null_phones_are_allowed(): void
    {
        $this->actingAs($this->admin(), 'web');
        foreach ([['name' => 'Name only'], ['phone' => '0'], ['email' => 'same@example.test'], ['email' => 'same@example.test']] as $data) {
            $this->post('/admin/customers', $data)->assertRedirect(route('admin.customers.index'))->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('customers', 4);
        $this->assertSame(3, Customer::whereNull('phone')->count());
        $this->assertSame(2, Customer::where('email', 'same@example.test')->count());
    }

    public function test_invalid_fields_and_unidentified_customers_are_rejected_with_old_values(): void
    {
        $this->actingAs($this->admin(), 'web');
        foreach ([['name', str_repeat('a', 256)], ['name', ['bad']], ['phone', 'bad'], ['phone', str_repeat('1', 21)], ['phone', ['bad']], ['email', 'bad'], ['email', ['bad']], ['birthday', '2001-02-29'], ['birthday', 'bad'], ['birthday', ['bad']]] as [$field, $value]) {
            $this->from('/admin/customers/create')->post('/admin/customers', $this->payload([$field => $value]))
                ->assertRedirect('/admin/customers/create')->assertSessionHasErrors($field);
        }
        foreach ([[], ['birthday' => '2000-01-01'], ['name' => '  ', 'phone' => '', 'email' => '']] as $data) {
            $this->post('/admin/customers', $data)->assertSessionHasErrors('name');
        }
        $this->assertDatabaseCount('customers', 0);
        $this->post('/admin/customers', $this->payload(['email' => 'bad']))->assertStatus(302);
        $this->get('/admin/customers/create')->assertOk()->assertSee('value="Customer Name"', false)->assertSee('email-error');
    }

    public function test_duplicate_phone_including_deleted_customer_is_rejected(): void
    {
        $this->actingAs($this->admin(), 'web');
        $customer = Customer::create($this->payload());
        foreach ([false, true] as $deleted) {
            if ($deleted) {
                $customer->delete();
            }
            $this->post('/admin/customers', $this->payload())->assertSessionHasErrors('phone');
        }
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_phone_claimed_after_validation_is_reported_as_validation_error(): void
    {
        $this->actingAs($this->admin(), 'web');
        Customer::saving(fn (Customer $customer) => DB::table('customers')->insert(['phone' => $customer->phone]));
        try {
            $this->from('/admin/customers/create')->post('/admin/customers', ['phone' => '0'])
                ->assertRedirect('/admin/customers/create')->assertSessionHasErrors('phone')->assertSessionMissing('success');
            $this->assertDatabaseCount('customers', 1);
        } finally {
            Customer::flushEventListeners();
        }
    }

    public function test_unexpected_database_failure_is_not_hidden(): void
    {
        $this->actingAs($this->admin(), 'web');
        Customer::saving(fn () => throw new QueryException('sqlite', 'test statement', [], new \RuntimeException('simulated failure')));
        $this->withoutExceptionHandling();
        try {
            $this->expectException(QueryException::class);
            $this->post('/admin/customers', $this->payload());
        } finally {
            Customer::flushEventListeners();
            $this->assertDatabaseCount('customers', 0);
        }
    }

    public function test_invalid_search_is_rejected_and_store_requires_csrf(): void
    {
        $this->actingAs($this->admin(), 'web');
        foreach (['search[]=bad', 'search='.str_repeat('a', 256)] as $query) {
            $this->get('/admin/customers?'.$query)->assertSessionHasErrors('search');
        }
        $this->app->instance('env', 'local');
        $this->post('/admin/customers', $this->payload())->assertStatus(419);
        $this->assertDatabaseCount('customers', 0);
    }
}
