<?php

namespace Tests\Feature\Auth;

use App\Models\Organisation;
use App\Models\Province;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response
            ->assertOk()
            ->assertSeeVolt('pages.auth.register');
    }

    public function test_new_users_can_register(): void
    {
        $province = Province::create([
            'code_province' => 'CD61',
            'nom_province' => 'Tanganyika',
            'is_active' => 'YES',
        ]);
        $organisation = Organisation::create([
            'org_name' => 'Organisation test',
            'is_active' => true,
        ]);

        $component = Volt::test('pages.auth.register')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('code_province', $province->code_province)
            ->set('org_id', $organisation->id);

        $component->call('register');

        $component->assertRedirect(route('login', absolute: false));

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_active' => false,
        ]);
    }
}
