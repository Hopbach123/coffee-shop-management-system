<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkflowScreenAvailabilityTest extends TestCase
{
    public function test_staff_sees_unavailable_message_instead_of_false_empty_state_before_migration(): void
    {
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        (require database_path('migrations/0001_01_01_000000_create_users_table.php'))->up();
        $this->withoutVite()->actingAs(User::forceCreate([
            'name' => 'UI tester', 'email' => 'ui@example.test', 'password' => 'test-only', 'role' => 'staff', 'status' => 'active',
        ]), 'web');
        $this->get('/staff/online-requests')->assertOk()->assertSee('Chưa thể tải yêu cầu')->assertDontSee('Không có yêu cầu phù hợp')->assertSee('Pha chế')->assertSee('Đặt bàn');
        $this->get('/staff/online-requests/1')->assertStatus(503);
        $this->get('/staff/online-requests?status[]=bad')->assertSessionHasErrors('status');
        $this->get('/staff/brewing')->assertOk()->assertSee('Chưa có món nào');
    }
}
