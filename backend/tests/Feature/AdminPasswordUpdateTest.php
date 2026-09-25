<?php

namespace Tests\Feature;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_update_admin_password(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('SuperPass123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Regular Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('OldPass123'),
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        // Superadmin updates admin's password to NewPass123
        $response = $this->actingAs($superAdmin)
            ->putJson('/api/admin/admins/' . $admin->id, [
                'name' => 'Regular Admin',
                'email' => 'admin@example.com',
                'password' => 'NewPass123',
                'status' => 'active',
            ]);

        $response->assertStatus(200);

        $admin->refresh();
        $this->assertTrue(
            Hash::check('NewPass123', $admin->password),
            'The admin password should have been updated to the new password.'
        );
        $this->assertFalse(
            Hash::check('OldPass123', $admin->password),
            'The old password should no longer be valid.'
        );
    }

    public function test_admin_password_remains_unchanged_when_password_field_is_empty_on_update(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'password' => Hash::make('SuperPass123'),
            'role' => UserRole::SuperAdmin,
            'is_active' => true,
        ]);

        $admin = User::create([
            'name' => 'Regular Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('OldPass123'),
            'role' => UserRole::Admin,
            'is_active' => true,
        ]);

        // Superadmin updates admin name and status without providing password
        $response = $this->actingAs($superAdmin)
            ->putJson('/api/admin/admins/' . $admin->id, [
                'name' => 'Regular Admin Renamed',
                'email' => 'admin@example.com',
                'status' => 'active',
            ]);

        $response->assertStatus(200);

        $admin->refresh();
        $this->assertEquals('Regular Admin Renamed', $admin->name);
        $this->assertTrue(
            Hash::check('OldPass123', $admin->password),
            'The admin password should remain unchanged.'
        );
    }
}
