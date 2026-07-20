<?php

namespace Tests\Feature\Users;

use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('users.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_users_list_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertOk();
    }

    public function test_users_list_displays_users(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);

        Livewire::actingAs($user)
            ->test(UserList::class)
            ->assertSee('John Doe');
    }

    public function test_users_can_be_searched(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        Livewire::actingAs($user)
            ->test(UserList::class)
            ->set('search', 'John')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    }

    public function test_user_can_be_deleted(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserList::class)
            ->call('delete', $target->id);

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_user_cannot_delete_own_account(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(UserList::class)
            ->call('delete', $user->id);

        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_create_user_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('users.create'));

        $response->assertOk();
    }

    public function test_new_user_can_be_created(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->set('name', 'New User')
            ->set('email', 'new@example.com')
            ->set('password', 'password123')
            ->call('save');

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'new@example.com',
        ]);
    }

    public function test_new_user_requires_name(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->set('email', 'new@example.com')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_new_user_requires_email(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->set('name', 'New User')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_new_user_requires_valid_email(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->set('name', 'New User')
            ->set('email', 'not-an-email')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_new_user_requires_unique_email(): void
    {
        $admin = User::factory()->create();
        User::factory()->create(['email' => 'taken@example.com']);

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->set('name', 'New User')
            ->set('email', 'taken@example.com')
            ->set('password', 'password123')
            ->call('save')
            ->assertHasErrors(['email']);
    }

    public function test_new_user_requires_password(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->set('name', 'New User')
            ->set('email', 'new@example.com')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_new_user_password_requires_minimum_8_characters(): void
    {
        $admin = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserForm::class)
            ->set('name', 'New User')
            ->set('email', 'new@example.com')
            ->set('password', 'short')
            ->call('save')
            ->assertHasErrors(['password']);
    }

    public function test_edit_user_page_can_be_rendered(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('users.edit', $target->id));

        $response->assertOk();
    }

    public function test_user_can_be_updated(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(UserForm::class, ['id' => $target->id])
            ->set('name', 'Updated Name')
            ->set('email', 'updated@example.com')
            ->call('save');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_user_password_can_be_changed_on_edit(): void
    {
        $admin = User::factory()->create();
        $target = User::factory()->create(['password' => Hash::make('old-password')]);

        Livewire::actingAs($admin)
            ->test(UserForm::class, ['id' => $target->id])
            ->set('name', $target->name)
            ->set('email', $target->email)
            ->set('password', 'new-password123')
            ->call('save');

        $this->assertTrue(
            Hash::check('new-password123', $target->refresh()->password)
        );
    }

    public function test_user_password_is_not_changed_when_null_on_edit(): void
    {
        $admin = User::factory()->create();
        $originalPassword = Hash::make('old-password');
        $target = User::factory()->create(['password' => $originalPassword]);

        Livewire::actingAs($admin)
            ->test(UserForm::class, ['id' => $target->id])
            ->set('name', 'Updated Name')
            ->set('email', $target->email)
            ->call('save');

        $refreshed = $target->refresh();
        $this->assertTrue(
            Hash::check('old-password', $refreshed->password)
        );
    }
}
