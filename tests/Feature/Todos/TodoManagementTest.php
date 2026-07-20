<?php

namespace Tests\Feature\Todos;

use App\Livewire\Todos\TodoForm;
use App\Livewire\Todos\TodoList;
use App\Models\Subtask;
use App\Models\Todo;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TodoManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('todos.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_todos_list_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('todos.index'));

        $response->assertOk();
    }

    public function test_todos_list_displays_todos(): void
    {
        $user = User::factory()->create();
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Buy milk']);

        Livewire::actingAs($user)
            ->test(TodoList::class)
            ->assertSee('Buy milk');
    }

    public function test_user_only_sees_own_todos(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Todo::factory()->create(['user_id' => $user->id, 'title' => 'My todo']);
        Todo::factory()->create(['user_id' => $other->id, 'title' => 'Other todo']);

        Livewire::actingAs($user)
            ->test(TodoList::class)
            ->assertSee('My todo')
            ->assertDontSee('Other todo');
    }

    public function test_todos_can_be_searched(): void
    {
        $user = User::factory()->create();

        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Buy milk']);
        Todo::factory()->create(['user_id' => $user->id, 'title' => 'Walk the dog']);

        Livewire::actingAs($user)
            ->test(TodoList::class)
            ->set('search', 'milk')
            ->assertSee('Buy milk')
            ->assertDontSee('Walk the dog');
    }

    public function test_todo_can_be_toggled(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->pending()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(TodoList::class)
            ->call('toggle', $todo->id);

        $this->assertTrue($todo->refresh()->completed);
    }

    public function test_todo_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(TodoList::class)
            ->call('delete', $todo->id);

        $this->assertDatabaseMissing('todos', ['id' => $todo->id]);
    }

    public function test_user_cannot_toggle_other_users_todo(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $todo = Todo::factory()->pending()->create(['user_id' => $other->id]);

        Livewire::actingAs($user)
            ->test(TodoList::class)
            ->call('toggle', $todo->id);

        $this->assertFalse($todo->refresh()->completed);
    }

    public function test_create_todo_page_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('todos.create'));

        $response->assertOk();
    }

    public function test_new_todo_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TodoForm::class)
            ->set('title', 'New Todo')
            ->set('description', 'A description')
            ->call('save');

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'New Todo',
            'description' => 'A description',
        ]);
    }

    public function test_new_todo_requires_title(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TodoForm::class)
            ->set('description', 'No title')
            ->call('save')
            ->assertHasErrors(['title']);
    }

    public function test_edit_todo_page_can_be_rendered(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('todos.edit', $todo->id));

        $response->assertOk();
    }

    public function test_todo_can_be_updated(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(TodoForm::class, ['id' => $todo->id])
            ->set('title', 'Updated Title')
            ->call('save');

        $this->assertDatabaseHas('todos', [
            'id' => $todo->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_user_cannot_edit_other_users_todo(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $other->id, 'title' => 'Original']);

        $this->expectException(ModelNotFoundException::class);

        Livewire::actingAs($user)
            ->test(TodoForm::class, ['id' => $todo->id]);
    }

    public function test_todo_with_due_date_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TodoForm::class)
            ->set('title', 'Todo with date')
            ->set('due_date', '2026-08-01')
            ->call('save');

        $todo = Todo::where('user_id', $user->id)->where('title', 'Todo with date')->first();

        $this->assertNotNull($todo);
        $this->assertEquals('2026-08-01', $todo->due_date->format('Y-m-d'));
    }

    public function test_todo_with_repeat_type_can_be_created(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TodoForm::class)
            ->set('title', 'Weekly todo')
            ->set('repeat_type', 'weekly')
            ->call('save');

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'repeat_type' => 'weekly',
        ]);
    }

    public function test_invalid_repeat_type_is_rejected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TodoForm::class)
            ->set('title', 'Bad repeat')
            ->set('repeat_type', 'hourly')
            ->call('save')
            ->assertHasErrors(['repeat_type']);
    }

    public function test_subtasks_can_be_added_to_todo(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TodoForm::class)
            ->set('title', 'Todo with subtasks')
            ->set('newSubtaskTitle', 'First subtask')
            ->call('addSubtask')
            ->set('newSubtaskTitle', 'Second subtask')
            ->call('addSubtask')
            ->call('save');

        $todo = Todo::where('title', 'Todo with subtasks')->first();

        $this->assertNotNull($todo);
        $this->assertCount(2, $todo->subtasks);
    }

    public function test_subtask_requires_title(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TodoForm::class)
            ->set('newSubtaskTitle', '')
            ->call('addSubtask')
            ->assertHasErrors(['newSubtaskTitle']);
    }

    public function test_subtask_can_be_removed_before_save(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TodoForm::class)
            ->set('title', 'Todo')
            ->set('newSubtaskTitle', 'Subtask 1')
            ->call('addSubtask')
            ->call('removeSubtask', 0)
            ->call('save');

        $todo = Todo::where('title', 'Todo')->first();

        $this->assertNotNull($todo);
        $this->assertCount(0, $todo->subtasks);
    }

    public function test_notes_can_be_saved(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TodoForm::class)
            ->set('title', 'Todo with notes')
            ->set('notes', 'These are my notes')
            ->call('save');

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'notes' => 'These are my notes',
        ]);
    }

    public function test_reminder_date_can_be_saved(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(TodoForm::class)
            ->set('title', 'Todo with reminder')
            ->set('reminder_date', '2026-08-01T09:00')
            ->call('save');

        $this->assertDatabaseHas('todos', [
            'user_id' => $user->id,
            'title' => 'Todo with reminder',
        ]);
    }

    public function test_subtask_can_be_toggled_from_list(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $subtask = Subtask::factory()->pending()->create(['todo_id' => $todo->id]);

        Livewire::actingAs($user)
            ->test(TodoList::class)
            ->call('toggleSubtask', $subtask->id);

        $this->assertTrue($subtask->refresh()->completed);
    }

    public function test_user_cannot_toggle_other_users_subtask(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $other->id]);
        $subtask = Subtask::factory()->pending()->create(['todo_id' => $todo->id]);

        Livewire::actingAs($user)
            ->test(TodoList::class)
            ->call('toggleSubtask', $subtask->id);

        $this->assertFalse($subtask->refresh()->completed);
    }

    public function test_todo_with_attachments_shows_count_in_list(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        $todo->attachments()->create([
            'filename' => 'file.pdf',
            'path' => 'todos/file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ]);

        Livewire::actingAs($user)
            ->test(TodoList::class)
            ->assertSee('1 file(s)');
    }

    public function test_todo_with_subtasks_shows_progress_in_list(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->create(['user_id' => $user->id]);
        Subtask::factory()->completed()->create(['todo_id' => $todo->id, 'title' => 'Done task']);
        Subtask::factory()->pending()->create(['todo_id' => $todo->id, 'title' => 'Pending task']);

        Livewire::actingAs($user)
            ->test(TodoList::class)
            ->assertSee('Done task')
            ->assertSee('Pending task');
    }
}
