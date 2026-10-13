<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerUpdateTest extends TestCase
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

    private function customer(array $attributes = []): Customer
    {
        return Customer::create(array_merge(['name' => 'Customer', 'phone' => '0901234567', 'email' => 'customer@example.test', 'birthday' => '2000-02-29'], $attributes));
    }

    private function payload(Customer $customer, array $attributes = []): array
    {
        return array_merge(['name' => $customer->name, 'phone' => $customer->phone, 'email' => $customer->email, 'birthday' => $customer->birthday?->format('Y-m-d')], $attributes);
    }

    public function test_edit_form_and_update_preserve_phone_and_ignore_unapproved_fields(): void
    {
        $customer = $this->customer()->fresh();
        $createdAt = $customer->getRawOriginal('created_at');
        $this->actingAs($this->admin(), 'web')->get('/admin/customers/'.$customer->id.'/edit')->assertOk()
            ->assertSee('value="0901234567"', false)->assertSee('value="2000-02-29"', false)->assertSee('name="_method" value="PUT"', false);
        $this->put('/admin/customers/'.$customer->id, $this->payload($customer, ['name' => 'Updated', 'email' => null, 'birthday' => null,
            'status' => 'inactive', 'points' => 999, 'total_spent' => 999, 'id' => 9999, 'created_at' => '2000-01-01', 'deleted_at' => '2000-01-01']))
            ->assertRedirect(route('admin.customers.index'))->assertSessionHas('success', 'Đã cập nhật khách hàng.');
        $customer->refresh();
        $this->assertSame('Updated', $customer->name);
        $this->assertSame('0901234567', $customer->phone);
        $this->assertNull($customer->email);
        $this->assertNull($customer->birthday);
        $this->assertNull($customer->deleted_at);
        $this->assertSame('active', $customer->status);
        $this->assertSame($createdAt, $customer->getRawOriginal('created_at'));
        $this->assertArrayNotHasKey('points', $customer->getAttributes());
        $this->assertArrayNotHasKey('total_spent', $customer->getAttributes());
    }

    public function test_duplicate_phone_and_invalid_fields_are_rejected_with_old_values(): void
    {
        $customer = $this->customer();
        $other = $this->customer(['phone' => '0909999999']);
        $this->actingAs($this->admin(), 'web');
        foreach ([['phone', $other->phone], ['name', str_repeat('a', 256)], ['phone', 'abc'], ['phone', str_repeat('1', 21)], ['email', 'bad'], ['birthday', '2001-02-29']] as [$field, $value]) {
            $this->from('/admin/customers/'.$customer->id.'/edit')->put('/admin/customers/'.$customer->id, $this->payload($customer, [$field => $value]))
                ->assertRedirect('/admin/customers/'.$customer->id.'/edit')->assertSessionHasErrors($field);
        }
        $other->delete();
        $this->put('/admin/customers/'.$customer->id, $this->payload($customer, ['phone' => $other->phone]))->assertSessionHasErrors('phone');
        $this->put('/admin/customers/'.$customer->id, $this->payload($customer, ['name' => 'Retained', 'email' => 'bad']))->assertStatus(302);
        $this->get('/admin/customers/'.$customer->id.'/edit')->assertOk()->assertSee('value="Retained"', false)->assertSee('email-error');
        $this->assertSame('Customer', $customer->fresh()->name);
    }

    public function test_status_only_mutates_status_and_repeats_are_safe(): void
    {
        $customer = $this->customer()->fresh();
        $before = $customer->getAttributes();
        $this->actingAs($this->admin(), 'web');
        DB::enableQueryLog();
        foreach (['inactive', 'inactive', 'active', 'active'] as $status) {
            $this->patch('/admin/customers/'.$customer->id.'/status', ['status' => $status, 'name' => 'Forged', 'phone' => '0', 'deleted_at' => '2000-01-01'])
                ->assertRedirect(route('admin.customers.index'))->assertSessionHas('success');
            $this->assertSame($status, $customer->fresh()->status);
        }
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(orders|reservations)\b/i', $query['query']);
        }
        DB::disableQueryLog();
        DB::flushQueryLog();
        foreach (['id', 'name', 'phone', 'email', 'birthday', 'created_at', 'deleted_at'] as $field) {
            $this->assertSame($before[$field], $customer->fresh()->getAttributes()[$field]);
        }
        foreach (['bad', null, ['active']] as $value) {
            $this->patch('/admin/customers/'.$customer->id.'/status', ['status' => $value])->assertSessionHasErrors('status');
        }
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_guest_staff_missing_and_deleted_targets_are_handled(): void
    {
        $customer = $this->customer();
        $this->get('/admin/customers/'.$customer->id.'/edit')->assertRedirect(route('login'));
        $this->put('/admin/customers/'.$customer->id, $this->payload($customer))->assertRedirect(route('login'));
        $this->patch('/admin/customers/'.$customer->id.'/status', ['status' => 'inactive'])->assertRedirect(route('login'));
        $staff = $this->admin();
        $staff->forceFill(['role' => 'staff'])->save();
        $this->actingAs($staff, 'web')->get('/admin/customers/'.$customer->id.'/edit')->assertForbidden();
        $this->put('/admin/customers/'.$customer->id, $this->payload($customer))->assertForbidden();
        $this->patch('/admin/customers/'.$customer->id.'/status', ['status' => 'inactive'])->assertForbidden();
        $staff->forceFill(['role' => 'admin'])->save();
        $customer->delete();
        foreach ([$customer->id, 9999] as $id) {
            $this->get('/admin/customers/'.$id.'/edit')->assertNotFound();
            $this->put('/admin/customers/'.$id, $this->payload($customer))->assertNotFound();
            $this->patch('/admin/customers/'.$id.'/status', ['status' => 'active'])->assertNotFound();
        }
    }

    public function test_csrf_protection_and_no_get_status(): void
    {
        $customer = $this->customer();
        $this->actingAs($this->admin(), 'web')->get('/admin/customers/'.$customer->id.'/status')->assertStatus(405);
        $this->app->instance('env', 'local');
        $this->put('/admin/customers/'.$customer->id, $this->payload($customer))->assertStatus(419);
        $this->patch('/admin/customers/'.$customer->id.'/status', ['status' => 'inactive'])->assertStatus(419);
    }

    public function test_nullable_fields_are_supported_but_identity_cannot_be_erased(): void
    {
        $customer = $this->customer();
        $this->actingAs($this->admin(), 'web');
        $this->put('/admin/customers/'.$customer->id, ['name' => null, 'phone' => null, 'email' => null, 'birthday' => '2000-01-01'])
            ->assertSessionHasErrors('name');
        $this->assertSame('Customer', $customer->fresh()->name);
        $this->put('/admin/customers/'.$customer->id, ['name' => null, 'phone' => null, 'email' => 'only@example.test', 'birthday' => null])
            ->assertRedirect(route('admin.customers.index'))->assertSessionHasNoErrors();
        $this->assertNull($customer->fresh()->name);
        $this->assertNull($customer->fresh()->phone);
        $this->assertSame('only@example.test', $customer->fresh()->email);
    }

    public function test_phone_collision_after_validation_preserves_existing_customer(): void
    {
        $customer = $this->customer();
        $this->actingAs($this->admin(), 'web');
        Customer::saving(function (Customer $target): void {
            if ($target->phone === '0') {
                DB::table('customers')->insert(['phone' => '0']);
            }
        });
        try {
            $this->from('/admin/customers/'.$customer->id.'/edit')->put('/admin/customers/'.$customer->id, $this->payload($customer, ['name' => 'Not saved', 'phone' => '0']))
                ->assertRedirect('/admin/customers/'.$customer->id.'/edit')->assertSessionHasErrors('phone')->assertSessionMissing('success');
            $this->assertSame('Customer', $customer->fresh()->name);
            $this->assertSame('0901234567', $customer->fresh()->phone);
        } finally {
            Customer::flushEventListeners();
        }
    }
}
