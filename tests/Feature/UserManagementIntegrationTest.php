<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class UserManagementIntegrationTest extends TestCase
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
        return User::forceCreate(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'local-test-password', 'role' => 'admin', 'status' => 'active']);
    }

    private function login(string $email): void
    {
        // A new guard must resolve the user from persisted state, as on a new HTTP request.
        Auth::forgetGuards();
        $this->post('/login', ['email' => $email, 'password' => 'local-test-password']);
    }

    private function logout(): void
    {
        $this->post('/logout')->assertRedirect(route('login'));
        Auth::forgetGuards();
    }

    public function test_created_account_can_login_and_lock_unlock_controls_future_login(): void
    {
        $admin = $this->admin();
        $this->login($admin->email);
        $this->assertAuthenticatedAs($admin, 'web');
        $this->post('/admin/users', [
            'name' => 'Integration Staff', 'email' => 'staff@example.test', 'phone' => null,
            'password' => 'local-test-password', 'password_confirmation' => 'local-test-password',
            'role' => 'staff', 'status' => 'active',
        ])->assertRedirect(route('admin.users.index'));
        $staff = User::where('email', 'staff@example.test')->firstOrFail();
        $hash = $staff->password;
        $this->assertTrue(Hash::check('local-test-password', $hash));
        $this->logout();
        $this->login($staff->email);
        $this->assertAuthenticatedAs($staff, 'web');
        $this->get('/staff')->assertOk();
        $this->get('/admin/users')->assertForbidden();
        $this->logout();

        $this->login($admin->email);
        $this->patch('/admin/users/'.$staff->id.'/status', ['status' => 'inactive'])
            ->assertRedirect(route('admin.users.index'))->assertSessionHas('success', 'Đã khóa tài khoản.');
        $this->logout();
        Auth::forgetGuards();
        $this->from('/login')->post('/login', ['email' => $staff->email, 'password' => 'local-test-password'])
            ->assertRedirect('/login')->assertSessionHasErrors('authentication');
        $this->assertGuest('web');

        $this->login($admin->email);
        $this->patch('/admin/users/'.$staff->id.'/status', ['status' => 'active'])
            ->assertRedirect(route('admin.users.index'))->assertSessionHas('success', 'Đã mở khóa tài khoản.');
        $this->logout();
        $this->login($staff->email);
        $this->assertAuthenticatedAs($staff->fresh(), 'web');
        $this->assertSame($hash, $staff->fresh()->password);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_edit_role_and_email_are_used_by_authentication_and_authorization(): void
    {
        $admin = $this->admin();
        $target = User::forceCreate(['name' => 'Staff', 'email' => 'old@example.test', 'password' => 'local-test-password', 'role' => 'staff', 'status' => 'active']);
        $hash = $target->password;
        $this->login($admin->email);
        $this->put('/admin/users/'.$target->id, ['name' => 'Promoted', 'email' => 'new@example.test', 'phone' => null, 'role' => 'admin', 'status' => 'active'])
            ->assertRedirect(route('admin.users.index'))->assertSessionHas('success', 'Đã cập nhật người dùng.');
        $this->logout();
        $this->from('/login')->post('/login', ['email' => 'old@example.test', 'password' => 'local-test-password'])
            ->assertSessionHasErrors('authentication');
        $this->assertGuest('web');
        $this->login('new@example.test');
        $this->get('/admin/users')->assertOk();
        $this->get('/staff')->assertOk();
        $this->logout();
        $this->login($admin->email);
        $this->put('/admin/users/'.$target->id, ['name' => 'Demoted', 'email' => 'new@example.test', 'phone' => null, 'role' => 'staff', 'status' => 'active'])
            ->assertRedirect(route('admin.users.index'));
        $this->logout();
        $this->login('new@example.test');
        $this->get('/admin/users')->assertForbidden();
        $this->get('/staff')->assertOk();
        $this->assertSame($hash, $target->fresh()->password);
    }

    public function test_listing_actions_confirm_status_change_and_show_account_errors(): void
    {
        $admin = $this->admin();
        $active = User::forceCreate(['name' => 'Active', 'email' => 'active@example.test', 'password' => 'local-test-password', 'role' => 'staff', 'status' => 'active']);
        User::forceCreate(['name' => 'Inactive', 'email' => 'inactive@example.test', 'password' => 'local-test-password', 'role' => 'staff', 'status' => 'inactive']);
        $this->actingAs($admin, 'web')->get('/admin/users')->assertOk()
            ->assertSee('Bạn có chắc muốn khóa tài khoản này?')->assertSee('Bạn có chắc muốn mở khóa tài khoản này?')
            ->assertSee(route('admin.users.edit', $active))->assertSee(route('admin.users.create'))
            ->assertDontSee(route('admin.users.status', $admin));
        $this->from('/admin/users')->patch('/admin/users/'.$admin->id.'/status', ['status' => 'inactive'])->assertRedirect('/admin/users');
        $this->get('/admin/users')->assertOk()->assertSee('Bạn không thể tự khóa tài khoản đang đăng nhập.');
    }

    public function test_user_routes_are_unique_admin_protected_and_have_no_delete_operation(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())->filter(fn ($route) => str_starts_with($route->uri(), 'admin/users'));
        $this->assertCount(6, $routes);
        $this->assertCount(6, $routes->map(fn ($route) => implode('|', $route->methods()).' '.$route->uri())->unique());
        $this->assertCount(6, $routes->map(fn ($route) => $route->getName())->unique());
        foreach ($routes as $route) {
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('role:admin', $route->gatherMiddleware());
            $this->assertNotContains('DELETE', $route->methods());
        }
    }
}
