<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run the actual users/session migration only; other migrations target MySQL.
        // Refuse to touch any persistent database when preparing auth fixtures.
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();

        Route::middleware(['web', 'auth:web'])->get('/auth-test/protected', fn () => 'Protected');
    }

    private function user(string $role = 'staff', string $status = 'active'): User
    {
        return User::forceCreate([
            'name' => 'Auth test user',
            'email' => 'auth-test@example.test',
            'password' => 'test-password',
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function test_guest_can_view_login_and_public_home(): void
    {
        $this->get('/login')->assertOk()->assertSee('autocomplete="current-password"', false)
            ->assertSee('name="_token"', false)->assertSee('type="submit"', false);
        $this->get('/')->assertOk()->assertSee('Maison du Café')->assertSee(route('login'));
    }

    public function test_email_is_required(): void
    {
        $this->from('/login')->post('/login', ['password' => 'test-password'])
            ->assertRedirect('/login')->assertSessionHasErrors('email');
        $this->assertGuest('web');
    }

    public function test_email_must_be_valid(): void
    {
        $this->from('/login')->post('/login', ['email' => 'invalid', 'password' => 'test-password'])
            ->assertSessionHasErrors('email')->assertSessionMissing('_old_input.password');
        $this->followingRedirects()->from('/login')->post('/login', ['email' => 'invalid', 'password' => 'test-password'])
            ->assertSee('email-error')->assertSee('aria-invalid="true"', false);
    }

    public function test_password_is_required(): void
    {
        $this->post('/login', ['email' => 'auth-test@example.test'])->assertSessionHasErrors('password');
        $this->assertGuest('web');
    }

    public function test_wrong_credentials_fail_without_updating_last_login(): void
    {
        $user = $this->user();
        $user->forceFill(['last_login_at' => now()->subDay()])->save();
        $previous = $user->fresh()->last_login_at;

        $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertRedirect('/login')->assertSessionHasErrors('authentication')
            ->assertSessionHas('_old_input.email', $user->email)->assertSessionMissing('_old_input.password');
        $this->assertGuest('web');
        $this->assertTrue($previous->equalTo($user->fresh()->last_login_at));
        $this->followingRedirects()->from('/login')->post('/login', ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertSee('Đăng nhập không thành công')->assertDontSee('wrong-password');
    }

    public function test_unknown_email_fails(): void
    {
        $this->post('/login', ['email' => 'unknown@example.test', 'password' => 'test-password'])
            ->assertSessionHasErrors('authentication');
        $this->assertGuest('web');
    }

    public function test_active_admin_can_login(): void
    {
        $this->assertSuccessfulLogin($this->user('admin'));
    }

    public function test_active_staff_can_login(): void
    {
        $this->assertSuccessfulLogin($this->user());
    }

    private function assertSuccessfulLogin(User $user): void
    {
        $this->travelTo(now()->startOfSecond());
        $session = $this->app['session.store'];
        $session->start();
        $previousId = $session->getId();

        $this->post('/login', ['email' => $user->email, 'password' => 'test-password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertNotSame($previousId, $session->getId());
        $this->assertTrue(now()->equalTo($user->fresh()->last_login_at));
        $this->get('/')->assertOk()->assertSee(route('logout'));
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = $this->user('staff', 'inactive');
        $this->post('/login', ['email' => $user->email, 'password' => 'test-password'])
            ->assertSessionHasErrors('authentication');
        $this->assertGuest('web');
        $this->assertNull($user->fresh()->last_login_at);
    }

    public function test_soft_deleted_user_cannot_login(): void
    {
        $user = $this->user();
        $user->delete();
        $this->post('/login', ['email' => $user->email, 'password' => 'test-password'])
            ->assertSessionHasErrors('authentication');
        $this->assertGuest('web');
    }

    public function test_logout_invalidates_session_and_regenerates_csrf_token(): void
    {
        $user = $this->user();
        $this->post('/login', ['email' => $user->email, 'password' => 'test-password']);
        $session = $this->app['session.store'];
        $previousId = $session->getId();
        $previousToken = $session->token();
        $session->put('private-test-data', 'should-be-removed');

        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('private-test-data');
        $this->assertGuest('web');
        $this->assertNotSame($previousId, $session->getId());
        $this->assertNotSame($previousToken, $session->token());
        $this->get('/auth-test/protected')->assertRedirect('/login');
    }

    public function test_guest_cannot_access_protected_route_or_logout(): void
    {
        $this->get('/auth-test/protected')->assertRedirect('/login');
        $this->post('/logout')->assertRedirect('/login');
        $this->get('/logout')->assertStatus(405);
    }

    public function test_login_redirects_to_intended_protected_route(): void
    {
        $user = $this->user();
        $this->get('/auth-test/protected')->assertRedirect('/login');
        $this->post('/login', ['email' => $user->email, 'password' => 'test-password'])
            ->assertRedirect('/auth-test/protected');
        $this->get('/auth-test/protected')->assertOk();
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $this->actingAs($this->user(), 'web')->get('/login')->assertRedirect('/');
    }

    public function test_login_and_logout_require_csrf_tokens(): void
    {
        // Laravel skips CSRF in testing; enable the real middleware check for these requests.
        $this->app->instance('env', 'local');
        $this->post('/login', ['email' => 'auth-test@example.test', 'password' => 'test-password'])
            ->assertStatus(419);
        $this->actingAs($this->user(), 'web')->post('/logout')->assertStatus(419);
    }
}
