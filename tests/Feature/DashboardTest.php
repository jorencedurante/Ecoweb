<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    private function requiresMysql(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('The dashboard chart query uses the MySQL-only DAYNAME() function.');
        }
    }

    public function test_super_admin_dashboard_loads(): void
    {
        $this->requiresMysql();

        $superAdmin = $this->createSuperAdmin();
        $teacher = $this->createTeacher();
        $this->createStudent($teacher);

        $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
    }

    public function test_teacher_dashboard_loads_with_scoped_students(): void
    {
        $this->requiresMysql();

        $teacher = $this->createTeacher();
        $this->createStudent($teacher);

        $response = $this->actingAs($teacher)->get(route('admin.dashboard'));

        $response->assertOk();
    }
}
