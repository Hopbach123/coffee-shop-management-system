<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['name' => 'New Staff', 'email' => 'new@example.test', 'phone' => '+84 (90) 123-4567', 'password' => 'test-only-password', 'password_confirmation' => 'test-only-password', 'role' => 'staff', 'status' => 'active'], $overrides);
    }

    public function test_admin_can_open_form_and_create_both_roles_without_switching_session(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'web')->get('/admin/users/create')->assertOk()->assertViewIs('admin.users.create')
            ->assertSee('name="_token"', false)->assertSee('name="password_confirmation"', false);
        foreach (['admin' => 'inactive', 'staff' => 'active'] as $role => $status) {
            $data = $this->payload(['email' => $role.'-new@example.test', 'role' => $role, 'status' => $status]);
            $this->post('/admin/users', $data)->assertRedirect(route('admin.users.index'))->assertSessionHas('success');
            $user = User::where('email', $data['email'])->firstOrFail();
            $this->assertSame($role, $user->role);
            $this->assertSame($status, $user->status);
            $this->assertSame($data['phone'], $user->phone);
            $this->assertNotSame($data['password'], $user->password);
            $this->assertTrue(Hash::check($data['password'], $user->password));
            $this->assertAuthenticatedAs($admin, 'web');
            $this->get('/admin/users')->assertOk()->assertSee('Đã thêm người dùng.');
        }
    }

    public function test_guest_and_staff_cannot_open_or_submit_form(): void
    {
        $this->get('/admin/users/create')->assertRedirect(route('login'));
        $this->post('/admin/users', $this->payload())->assertRedirect(route('login'));
        $staff = $this->admin();
        $staff->forceFill(['role' => 'staff'])->save();
        $this->actingAs($staff, 'web')->get('/admin/users/create')->assertForbidden();
        $this->post('/admin/users', $this->payload())->assertForbidden();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_validation_rejects_invalid_fields_without_persisting_or_flashing_passwords(): void
    {
        $this->actingAs($this->admin(), 'web');
        foreach ([['name', ''], ['name', str_repeat('a', 256)], ['email', ''], ['email', 'invalid'], ['email', ['invalid']], ['phone', 'abc'], ['phone', str_repeat('1', 21)], ['phone', ['invalid']], ['password', ''], ['password', 'short'], ['password_confirmation', 'mismatch'], ['role', 'customer'], ['role', ''], ['status', 'available'], ['status', '']] as [$field, $value]) {
            $error = $field === 'password_confirmation' ? 'password' : $field;
            $this->from('/admin/users/create')->post('/admin/users', $this->payload([$field => $value]))
                ->assertRedirect('/admin/users/create')->assertSessionHasErrors($error)
                ->assertSessionMissing('_old_input.password')->assertSessionMissing('_old_input.password_confirmation');
        }
        $this->assertDatabaseCount('users', 1);
        $this->from('/admin/users/create')->post('/admin/users', $this->payload(['status' => 'invalid']))
            ->assertRedirect('/admin/users/create');
        $this->get('/admin/users/create')->assertOk()->assertSee('value="New Staff"', false)
            ->assertSee('status-error')->assertDontSee('value="test-only-password"', false);
    }

    public function test_duplicate_email_including_soft_deleted_accounts_is_rejected(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'web');
        foreach ([false, true] as $deleted) {
            if ($deleted) {
                $admin->delete();
            }
            $this->post('/admin/users', $this->payload(['email' => $admin->email]))->assertSessionHasErrors('email');
        }
        $this->assertDatabaseCount('users', 1);
    }

    public function test_unapproved_fields_are_ignored_and_phone_is_optional(): void
    {
        $this->actingAs($this->admin(), 'web')->post('/admin/users', $this->payload([
            'phone' => null, 'id' => 9999, 'avatar' => 'unapproved.png', 'last_login_at' => '2000-01-01',
            'created_at' => '2000-01-01', 'updated_at' => '2000-01-01', 'deleted_at' => '2000-01-01',
        ]))->assertRedirect(route('admin.users.index'));
        $user = User::where('email', 'new@example.test')->firstOrFail();
        $this->assertNull($user->phone);
        $this->assertNull($user->avatar);
        $this->assertNull($user->last_login_at);
        $this->assertNull($user->deleted_at);
        $this->assertNotSame(9999, $user->id);
        $this->assertNotSame('2000-01-01', $user->created_at->format('Y-m-d'));
        $this->assertNotSame('2000-01-01', $user->updated_at->format('Y-m-d'));
    }

    public function test_email_claimed_after_validation_returns_safe_validation_error(): void
    {
        $this->actingAs($this->admin(), 'web');
        User::saving(function (User $user): void {
            if ($user->email === 'new@example.test') {
                DB::table('users')->insert(['name' => 'Concurrent', 'email' => $user->email, 'password' => Hash::make('test-only-password'), 'role' => 'staff', 'status' => 'active']);
            }
        });
        try {
            $this->from('/admin/users/create')->post('/admin/users', $this->payload())
                ->assertRedirect('/admin/users/create')->assertSessionHasErrors('email')
                ->assertSessionMissing('success')->assertSessionMissing('_old_input.password');
            $this->assertDatabaseCount('users', 2);
        } finally {
            User::flushEventListeners();
        }
    }

    public function test_unrelated_database_error_is_not_reported_as_success(): void
    {
        $this->actingAs($this->admin(), 'web');
        User::saving(fn () => throw new QueryException('sqlite', 'test statement', [], new \RuntimeException('simulated failure')));
        $this->withoutExceptionHandling();
        try {
            $this->expectException(QueryException::class);
            $this->post('/admin/users', $this->payload());
        } finally {
            User::flushEventListeners();
            $this->assertDatabaseCount('users', 1);
        }
    }

    public function test_store_requires_csrf(): void
    {
        $this->actingAs($this->admin(), 'web');
        $this->app->instance('env', 'local');
        $this->post('/admin/users', $this->payload())->assertStatus(419);
        $this->assertDatabaseCount('users', 1);
    }
}
