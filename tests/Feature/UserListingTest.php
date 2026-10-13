<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserListingTest extends TestCase
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
        return User::forceCreate(array_merge([
            'name' => 'Local Admin',
            'email' => 'user-'.User::withTrashed()->count().'@example.test',
            'password' => 'test-password-only',
            'role' => 'admin',
            'status' => 'active',
        ], $attributes));
    }

    public function test_guest_is_redirected_and_staff_is_forbidden(): void
    {
        $this->get('/admin/users')->assertRedirect(route('login'));
        $staff = $this->user(['role' => 'staff']);
        $this->actingAs($staff, 'web')->get('/admin/users')->assertForbidden();
        $this->assertAuthenticatedAs($staff, 'web');
    }

    public function test_admin_sees_approved_fields_roles_and_statuses_only(): void
    {
        $admin = $this->user();
        $staff = $this->user(['name' => 'Staff Example', 'role' => 'staff', 'status' => 'inactive', 'phone' => '0901234567']);
        $this->actingAs($admin, 'web')->get('/admin/users')->assertOk()
            ->assertViewIs('admin.users.index')->assertSee('Staff Example')->assertSee($staff->email)
            ->assertSee('0901234567')->assertSee('Quản trị viên')->assertSee('Nhân viên')
            ->assertSee('Đang hoạt động')->assertSee('Ngừng hoạt động')
            ->assertSee($staff->created_at->format('d/m/Y H:i'))
            ->assertDontSee($staff->password)->assertDontSee('test-password-only')
            ->assertViewHas('users', function ($users) {
                foreach ($users as $user) {
                    $this->assertSame(['id', 'name', 'email', 'phone', 'role', 'status', 'created_at'], array_keys($user->getAttributes()));
                }

                return true;
            });
    }

    public function test_search_matches_name_or_email_and_excludes_soft_deleted_users(): void
    {
        $admin = $this->user();
        $named = $this->user(['name' => 'Needle Person']);
        $emailed = $this->user(['name' => 'Other', 'email' => 'needle@example.test']);
        $this->user(['email' => 'needle-deleted@example.test'])->delete();
        $this->actingAs($admin, 'web')->get('/admin/users?search=Needle')->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 2
                && $users->pluck('id')->sort()->values()->all() === [$named->id, $emailed->id]);
        $this->get('/admin/users?search=needle%40example.test')->assertOk()
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$emailed->id]);
        $this->get('/admin/users?search='.urlencode("' OR 1=1 --"))->assertOk()
            ->assertViewHas('users', fn ($users) => $users->total() === 0);
    }

    public function test_pagination_retains_search_and_sorts_newest_first_with_id_tiebreaker(): void
    {
        $admin = $this->user();
        $ids = [];
        for ($i = 0; $i < 16; $i++) {
            $ids[] = $this->user(['name' => 'Match '.$i, 'created_at' => '2026-01-01 12:00:00'])->id;
        }
        $newest = $this->user(['name' => 'Match newest', 'created_at' => '2026-02-01 12:00:00']);
        $this->actingAs($admin, 'web')->get('/admin/users?search=Match')->assertOk()
            ->assertSee('search=Match&amp;page=2', false)
            ->assertViewHas('users', fn ($users) => $users->total() === 17 && $users->count() === 15 && $users->first()->id === $newest->id);
        $this->get('/admin/users?search=Match&page=2')->assertOk()
            ->assertViewHas('users', fn ($users) => $users->pluck('id')->all() === [$ids[1], $ids[0]]
                && str_contains($users->previousPageUrl(), 'search=Match'));
    }

    public function test_empty_and_no_match_states_are_distinct_and_output_is_escaped(): void
    {
        $admin = $this->user();
        $this->actingAs($admin, 'web');
        $admin->delete();
        $this->get('/admin/users')->assertOk()->assertSee('Chưa có người dùng');
        $this->get('/admin/users?search=Missing')->assertOk()->assertSee('Không tìm thấy người dùng');
        $this->user(['name' => '<script>alert(1)</script>']);
        $this->get('/admin/users')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_invalid_search_is_rejected(): void
    {
        $this->actingAs($this->user(), 'web');
        foreach (['search[]=invalid', 'search='.str_repeat('a', 256)] as $query) {
            $this->get('/admin/users?'.$query)->assertSessionHasErrors('search');
        }
    }
}
