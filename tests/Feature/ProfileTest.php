<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/akun')->assertOk()->assertSee('Data diri');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/akun', [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'phone' => '081234567890',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/akun');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertSame('081234567890', $user->phone);
        $this->assertNull($user->email_verified_at);
    }

    public function test_phone_must_be_an_indonesian_mobile_number(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/akun', ['name' => $user->name, 'email' => $user->email, 'phone' => '12345'])
            ->assertSessionHasErrors('phone');
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/akun', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/akun');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_account_cannot_be_deleted_from_the_shop(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->delete('/akun', ['password' => 'password'])->assertMethodNotAllowed();
        $this->assertNotNull($user->fresh());
    }
}
