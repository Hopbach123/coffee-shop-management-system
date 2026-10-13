<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Tests\TestCase;

class CustomerDeleteTest extends TestCase
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

    public function test_unreferenced_profile_is_soft_deleted_and_phone_stays_reserved(): void
    {
        $customer = Customer::create(['name' => 'Removed Customer', 'phone' => '0901234567']);
        $this->actingAs($this->admin(), 'web')->delete('/admin/customers/'.$customer->id)
            ->assertRedirect(route('admin.customers.index'))->assertSessionHas('success');
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertDatabaseCount('customers', 1);
        $this->assertNull(Customer::find($customer->id));
        $this->get('/admin/customers')->assertOk()->assertDontSee('Removed Customer')->assertSee('Đã xóa mềm hồ sơ khách hàng.');
        $this->post('/admin/customers', ['phone' => '0901234567'])->assertSessionHasErrors('phone');
        $this->delete('/admin/customers/'.$customer->id)->assertNotFound();
        $this->delete('/admin/customers/9999')->assertNotFound();
    }

    public function test_guest_staff_and_csrf_cannot_delete(): void
    {
        $customer = Customer::create(['name' => 'Keep']);
        $this->delete('/admin/customers/'.$customer->id)->assertRedirect(route('login'));
        $staff = $this->admin();
        $staff->forceFill(['role' => 'staff'])->save();
        $this->actingAs($staff, 'web')->delete('/admin/customers/'.$customer->id)->assertForbidden();
        $staff->forceFill(['role' => 'admin'])->save();
        $this->get('/admin/customers/'.$customer->id)->assertStatus(405);
        $this->app->instance('env', 'local');
        $this->delete('/admin/customers/'.$customer->id)->assertStatus(419);
        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'deleted_at' => null]);
    }

    public function test_database_failure_returns_safe_error_and_preserves_row(): void
    {
        Exceptions::fake();
        $customer = Customer::create(['name' => 'Keep']);
        $this->actingAs($this->admin(), 'web');
        Customer::deleting(fn () => throw new QueryException('sqlite', 'fixture SQL', [], new \RuntimeException('fixture database failure')));
        try {
            $this->delete('/admin/customers/'.$customer->id)->assertRedirect(route('admin.customers.index'));
            $this->get('/admin/customers')->assertOk()->assertSee('Không thể xóa hồ sơ khách hàng.')
                ->assertDontSee('fixture database failure')->assertDontSee('fixture SQL');
            $this->assertDatabaseHas('customers', ['id' => $customer->id, 'deleted_at' => null]);
            Exceptions::assertReported(QueryException::class);
        } finally {
            Customer::flushEventListeners();
        }
    }

    public function test_cancelled_model_delete_never_reports_success(): void
    {
        $customer = Customer::create(['name' => 'Keep']);
        $this->actingAs($this->admin(), 'web');
        Customer::deleting(fn () => false);
        try {
            $this->delete('/admin/customers/'.$customer->id)->assertSessionHasErrors('delete')->assertSessionMissing('success');
            $this->assertDatabaseHas('customers', ['id' => $customer->id, 'deleted_at' => null]);
        } finally {
            Customer::flushEventListeners();
        }
    }
}
