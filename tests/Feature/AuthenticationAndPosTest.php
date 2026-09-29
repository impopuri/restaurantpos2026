<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationAndPosTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('pos'))
            ->assertRedirect(route('login'));
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->post(route('login.store'), [
            'identifier' => 'cashier',
            'password' => 'wrong-password',
        ])
            ->assertSessionHasErrors('identifier');
    }

    public function test_cashier_can_login_view_pos_and_logout(): void
    {
        User::factory()->create([
            'username' => 'cashier',
            'email' => 'cashier@example.com',
        ]);

        $this->post(route('login.store'), [
            'identifier' => 'cashier',
            'password' => 'password',
        ])
            ->assertRedirect(route('pos'));

        $this->get(route('pos'))
            ->assertOk()
            ->assertSee('Welcome, cashier!')
            ->assertSee('Meals')
            ->assertSee('Snacks')
            ->assertSee('Drinks')
            ->assertSee('Extras');

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->get(route('pos'))
            ->assertRedirect(route('login'));
    }

    public function test_superadmin_login_redirects_to_admin_area(): void
    {
        User::factory()->create([
            'name' => 'POS Admin',
            'username' => 'superadmin',
            'email' => 'admin@example.com',
            'role' => 'superadmin',
        ]);

        $this->post(route('login.store'), [
            'identifier' => 'superadmin',
            'password' => 'password',
        ])->assertRedirect(route('superadmin.dashboard'));
    }
}