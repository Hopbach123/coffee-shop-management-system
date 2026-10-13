<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EditUserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        $this->withoutVite();
    }

    private function user(array $attributes = []): User
    {
        return User::forceCreate(array_merge(['name' => 'User '.User::count(), 'email' => 'user-'.User::withTrashed()->count().'@example.test', 'password' => 'test-only-password', 'role' => 'admin', 'status' => 'active'], $attributes));
    }

    private function payload(User $user, array $overrides = []): array
    {
        return array_merge($user->only(['name', 'email', 'phone', 'role', 'status']), $overrides);
    }

    public function test_edit_form_shows_current_fields_without_password(): void
    {
        $admin = $this->user();
        $target = $this->user(['name' => 'Staff Name', 'role' => 'staff', 'status' => 'inactive', 'phone' => '0901234567']);
        $this->actingAs($admin, 'web')->get('/admin/users/'.$target->id.'/edit')->assertOk()
            ->assertViewIs('admin.users.edit')->assertSee('value="Staff Name"', false)
            ->assertSee('value="0901234567"', false)->assertSee('name="_method" value="PUT"', false)
            ->assertDontSee('name="password"', false)->assertDontSee($target->password)
            ->assertViewHas('user', fn ($user) => ! array_key_exists('password', $user->getAttributes()));
    }

    public function test_update_keeps_email_and_auth_fields_while_ignoring_forged_input(): void
    {
        $admin = $this->user();
        $target = $this->user(['role' => 'staff', 'avatar' => 'existing.png', 'last_login_at' => '2026-01-01 12:00:00']);
        $before = $target->fresh()->getAttributes();
        $adminBefore = $admin->fresh()->getAttributes();
        $this->actingAs($admin, 'web')->put('/admin/users/'.$target->id, $this->payload($target, [
            'name' => 'Updated Name', 'phone' => null, 'role' => 'admin', 'status' => 'inactive',
            'password' => 'forged-password', 'avatar' => 'forged.png', 'last_login_at' => '2000-01-01',
            'id' => 9999, 'created_at' => '2000-01-01', 'deleted_at' => '2000-01-01',
        ]))->assertRedirect(route('admin.users.index'))->assertSessionHas('success');
        $target->refresh();
        foreach (['id', 'email', 'password', 'avatar', 'last_login_at', 'created_at', 'deleted_at'] as $field) {
            $this->assertSame($before[$field], $target->getAttributes()[$field]);
        }
        $this->assertSame('Updated Name', $target->name);
        $this->assertSame('admin', $target->role);
        $this->assertSame('inactive', $target->status);
        $this->assertNull($target->phone);
        $this->assertSame($adminBefore, $admin->fresh()->getAttributes());
        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_invalid_update_values_and_duplicate_emails_are_rejected(): void
    {
        $admin = $this->user();
        $target = $this->user(['role' => 'staff']);
        $deleted = $this->user();
        $deleted->delete();
        $this->actingAs($admin, 'web');
        foreach ([['name', ''], ['name', str_repeat('a', 256)], ['email', 'bad'], ['email', $admin->email], ['email', $deleted->email], ['phone', 'abc'], ['phone', str_repeat('1', 21)], ['role', 'customer'], ['status', 'available'], ['role', ''], ['status', '']] as [$field, $value]) {
            $this->from('/admin/users/'.$target->id.'/edit')->put('/admin/users/'.$target->id, $this->payload($target, [$field => $value]))
                ->assertRedirect('/admin/users/'.$target->id.'/edit')->assertSessionHasErrors($field);
        }
        $this->assertSame($target->name, $target->fresh()->name);
        $this->put('/admin/users/'.$target->id, $this->payload($target, ['name' => 'Retained', 'phone' => 'bad']))->assertStatus(302);
        $this->get('/admin/users/'.$target->id.'/edit')->assertOk()->assertSee('value="Retained"', false)->assertSee('phone-error');
    }

    public function test_lock_unlock_changes_only_status_and_repeated_requests_do_not_reverse_it(): void
    {
        $admin = $this->user();
        $target = $this->user(['role' => 'staff']);
        $before = $target->fresh()->getAttributes();
        $this->actingAs($admin, 'web');
        foreach (['inactive', 'inactive', 'active', 'active'] as $status) {
            $this->patch('/admin/users/'.$target->id.'/status', ['status' => $status, 'role' => 'admin', 'name' => 'Forged', 'password' => 'forged'])
                ->assertRedirect(route('admin.users.index'))->assertSessionHas('success');
            $this->assertSame($status, $target->fresh()->status);
        }
        foreach (['name', 'email', 'role', 'password', 'phone', 'created_at', 'deleted_at'] as $field) {
            $this->assertSame($before[$field], $target->fresh()->getAttributes()[$field]);
        }
        $this->patch('/admin/users/'.$target->id.'/status', ['status' => 'bad'])->assertSessionHasErrors('status');
        $this->assertDatabaseCount('users', 2);
    }

    public function test_self_lock_is_rejected_even_with_another_active_admin(): void
    {
        $admin = $this->user();
        $this->user();
        $this->actingAs($admin, 'web');
        $this->from('/admin/users')->patch('/admin/users/'.$admin->id.'/status', ['status' => 'inactive'])->assertSessionHasErrors('account');
        $this->put('/admin/users/'.$admin->id, $this->payload($admin, ['status' => 'inactive']))->assertSessionHasErrors('account');
        $this->assertSame('active', $admin->fresh()->status);
    }

    public function test_last_active_admin_cannot_be_demoted_and_inactive_or_deleted_admins_do_not_count(): void
    {
        $admin = $this->user();
        $this->user(['status' => 'inactive']);
        $this->user()->delete();
        $this->actingAs($admin, 'web')->put('/admin/users/'.$admin->id, $this->payload($admin, ['role' => 'staff']))
            ->assertSessionHasErrors('account');
        $this->assertSame('admin', $admin->fresh()->role);
        $this->patch('/admin/users/'.$admin->id.'/status', ['status' => 'inactive'])->assertSessionHasErrors('account');
    }

    public function test_admin_can_demote_or_lock_another_admin_and_self_demote_when_another_remains(): void
    {
        $admin = $this->user();
        $target = $this->user();
        $this->actingAs($admin, 'web')->patch('/admin/users/'.$target->id.'/status', ['status' => 'inactive'])->assertSessionHasNoErrors();
        $this->patch('/admin/users/'.$target->id.'/status', ['status' => 'active'])->assertSessionHasNoErrors();
        $this->put('/admin/users/'.$target->id, $this->payload($target, ['role' => 'staff']))->assertSessionHasNoErrors();
        $this->assertSame('staff', $target->fresh()->role);
        $this->user();
        $this->put('/admin/users/'.$admin->id, $this->payload($admin, ['role' => 'staff']))->assertSessionHasNoErrors();
        $this->assertSame('staff', $admin->fresh()->role);
    }

    public function test_stale_actor_cannot_remove_remaining_admin_after_being_demoted(): void
    {
        $first = $this->user();
        $second = $this->user();
        $service = app(UserManagementService::class);
        $service->update($second, $first, ['role' => 'staff']);
        $this->assertSame('admin', $first->role); // Original model is deliberately stale.
        $this->expectException(AuthorizationException::class);
        try {
            $service->update($first, $second, ['role' => 'staff']);
        } finally {
            $this->assertSame('admin', $second->fresh()->role);
        }
    }

    public function test_write_occurs_inside_transaction_and_error_rolls_back(): void
    {
        $admin = $this->user();
        $target = $this->user(['role' => 'staff']);
        User::updated(function (): void {
            $this->assertGreaterThan(0, DB::transactionLevel());
            throw new \RuntimeException('simulated write failure');
        });
        try {
            $this->expectException(\RuntimeException::class);
            app(UserManagementService::class)->update($admin, $target, ['name' => 'Not persisted']);
        } finally {
            User::flushEventListeners();
            $this->assertSame($target->name, $target->fresh()->name);
        }
    }

    public function test_guest_staff_missing_and_deleted_targets_are_handled(): void
    {
        $target = $this->user();
        $this->get('/admin/users/'.$target->id.'/edit')->assertRedirect(route('login'));
        $this->put('/admin/users/'.$target->id, $this->payload($target))->assertRedirect(route('login'));
        $this->patch('/admin/users/'.$target->id.'/status', ['status' => 'inactive'])->assertRedirect(route('login'));
        $staff = $this->user(['role' => 'staff']);
        $this->actingAs($staff, 'web')->get('/admin/users/'.$target->id.'/edit')->assertForbidden();
        $this->put('/admin/users/'.$target->id, $this->payload($target))->assertForbidden();
        $this->patch('/admin/users/'.$target->id.'/status', ['status' => 'inactive'])->assertForbidden();
        $this->actingAs($this->user(), 'web');
        $target->delete();
        foreach ([$target->id, 99999] as $id) {
            $this->get('/admin/users/'.$id.'/edit')->assertNotFound();
            $this->put('/admin/users/'.$id, $this->payload($target))->assertNotFound();
            $this->patch('/admin/users/'.$id.'/status', ['status' => 'active'])->assertNotFound();
        }
    }

    public function test_csrf_and_http_methods_protect_mutations(): void
    {
        $admin = $this->user();
        $target = $this->user(['role' => 'staff']);
        $this->actingAs($admin, 'web');
        $this->get('/admin/users/'.$target->id.'/status')->assertStatus(405);
        $this->app->instance('env', 'local');
        $this->put('/admin/users/'.$target->id, $this->payload($target))->assertStatus(419);
        $this->patch('/admin/users/'.$target->id.'/status', ['status' => 'inactive'])->assertStatus(419);
        $this->assertSame('active', $target->fresh()->status);
    }
}
