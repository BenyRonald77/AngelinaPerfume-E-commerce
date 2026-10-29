<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_is_a_buyer(): void
    {
        $user = User::factory()->create();

        $this->assertSame(Role::Pembeli, $user->fresh()->role);
    }

    public function test_admin_can_open_panel(): void
    {
        $admin = User::factory()->create();
        $admin->role = Role::Admin;
        $admin->save();

        $this->actingAs($admin)->get('/admin')->assertOk();
    }

    public function test_buyer_cannot_open_panel(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)->get('/admin')->assertForbidden();
    }

    public function test_guest_is_sent_to_admin_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_admin_seeder_uses_env_credentials(): void
    {
        config(['toko.admin' => ['name' => 'Pemilik', 'email' => 'pemilik@angelia.test', 'password' => 'rahasia-panjang']]);

        $this->seed(\Database\Seeders\AdminSeeder::class);

        $admin = User::where('email', 'pemilik@angelia.test')->firstOrFail();
        $this->assertTrue($admin->isAdmin());
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_google_login_is_hidden_without_keys(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get('/auth/google')->assertNotFound();
    }
}
