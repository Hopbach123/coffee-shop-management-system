<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();

        Route::middleware(['web', 'auth:web', 'role:admin'])->get('/role-test/admin', fn () => 'Admin');
        Route::middleware(['web', 'auth:web', 'role:admin,staff'])->get('/role-test/staff', fn () => 'Staff');
        Route::middleware(['web', 'auth:web', 'role:admin,staff'])->get('/role-test/shared', fn () => 'Shared');
        Route::middleware(['web', 'role:admin'])->get('/role-test/role-only', fn () => 'Admin');
        Route::middleware(['web', 'auth:web', 'role'])->get('/role-test/no-roles', fn () => 'Denied');
    }

    private function user(string $role): User
    {
        return User::forceCreate([
            'name' => 'Role test user',
            'email' => $role.'@example.test',
            'password' => 'test-password',
            'role' => $role,
            'status' => 'active',
        ]);
    }

    public function test_guests_are_redirected_from_both_protected_routes(): void
    {
        $this->get('/role-test/admin')->assertRedirect('/login');
        $this->get('/role-test/staff')->assertRedirect('/login');
        $this->getJson('/role-test/admin')->assertUnauthorized();
    }

    public function test_role_middleware_alone_does_not_allow_guests(): void
    {
        $this->get('/role-test/role-only')->assertRedirect('/login');
    }

    public function test_admin_is_allowed_on_admin_only_route(): void
    {
        $this->actingAs($this->user('admin'), 'web')->get('/role-test/admin')->assertOk()->assertSee('Admin');
    }

    public function test_staff_is_forbidden_from_admin_without_losing_session(): void
    {
        $user = $this->user('staff');
        $this->post('/login', ['email' => $user->email, 'password' => 'test-password'])->assertRedirect(route('staff.home'));
        $this->withSession(['role-test-marker' => 'preserved'])->get('/role-test/admin')
            ->assertForbidden()->assertSessionHas('role-test-marker', 'preserved');
        $this->assertAuthenticatedAs($user, 'web');
        $this->get('/role-test/staff')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest('web');
    }

    public function test_staff_is_allowed_on_staff_area_route(): void
    {
        $this->actingAs($this->user('staff'), 'web')->get('/role-test/staff')->assertOk();
    }

    public function test_admin_is_allowed_on_staff_area_route(): void
    {
        $user = $this->user('admin');
        $this->actingAs($user, 'web')->get('/role-test/staff')->assertOk();
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_multiple_roles_allow_both_internal_roles(): void
    {
        $this->actingAs($this->user('admin'), 'web')->get('/role-test/shared')->assertOk();
        $this->actingAs($this->user('staff'), 'web')->get('/role-test/shared')->assertOk();
    }

    public function test_missing_role_configuration_denies_access(): void
    {
        $this->actingAs($this->user('admin'), 'web')->get('/role-test/no-roles')->assertForbidden();
    }

    public function test_json_wrong_role_is_forbidden_without_redirect(): void
    {
        $this->actingAs($this->user('staff'), 'web')->getJson('/role-test/admin')->assertForbidden();
    }

    public function test_role_checks_do_not_protect_public_home(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_real_internal_pages_require_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/staff')->assertRedirect('/login');
    }

    public function test_admin_can_use_both_interfaces(): void
    {
        $this->actingAs($this->user('admin'), 'web');
        $this->get('/admin')->assertOk()->assertSee(route('staff.home'));
        $this->get('/staff')->assertOk()->assertSee(route('admin.home'));
        $this->get('/login')->assertRedirect(route('admin.home'));
    }

    public function test_staff_interface_hides_admin_navigation_and_enforces_access(): void
    {
        $this->actingAs($this->user('staff'), 'web');
        $this->get('/staff')->assertOk()->assertSee(route('logout'))->assertDontSee(route('admin.home'));
        $this->get('/admin')->assertForbidden();
        $this->post('/logout')->assertRedirect('/login');
        $this->get('/staff')->assertRedirect('/login');
    }
}
