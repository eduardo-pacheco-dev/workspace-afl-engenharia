<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ForcePasswordChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('force-password-change'));

        $response->assertRedirect(route('login'));
    }

    public function test_users_without_must_change_password_can_access_dashboard(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_users_with_must_change_password_are_redirected(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('force-password-change'));
    }

    public function test_force_password_change_page_can_be_rendered(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->get(route('force-password-change'));

        $response->assertOk();
    }

    public function test_password_can_be_changed(): void
    {
        $user = User::factory()->create([
            'must_change_password' => true,
            'password' => \Illuminate\Support\Facades\Hash::make('old-password'),
        ]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Auth\ForcePasswordChange::class)
            ->set('password', 'new-password123')
            ->set('password_confirmation', 'new-password123')
            ->call('save');

        $this->assertTrue(
            \Illuminate\Support\Facades\Hash::check('new-password123', $user->refresh()->password)
        );
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'must_change_password' => false,
        ]);
    }

    public function test_password_requires_minimum_8_characters(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Auth\ForcePasswordChange::class)
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_password_confirmation_must_match(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Auth\ForcePasswordChange::class)
            ->set('password', 'new-password123')
            ->set('password_confirmation', 'different-password')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_password_is_required(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Auth\ForcePasswordChange::class)
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_must_change_password_is_cleared_after_save(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Auth\ForcePasswordChange::class)
            ->set('password', 'new-password123')
            ->set('password_confirmation', 'new-password123')
            ->call('save');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'must_change_password' => false,
        ]);
    }

    public function test_user_can_access_force_password_change_page_when_flag_is_true(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        Livewire::actingAs($user)
            ->test(\App\Livewire\Auth\ForcePasswordChange::class)
            ->assertOk();
    }

    public function test_force_password_change_route_excludes_must_change_password_middleware(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);

        $response = $this->actingAs($user)->get(route('force-password-change'));

        $response->assertOk();
    }
}
