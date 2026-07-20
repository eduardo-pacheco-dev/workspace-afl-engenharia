<?php

namespace App\Livewire\Todos;

use App\Models\Subtask;
use App\Models\Todo;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app.sidebar')]
#[Title('Todos')]
class TodoList extends Component
{
    use WithPagination;

    public string $search = '';

    public function render()
    {
        return view('livewire.todos.todo-list', [
            'todos' => Todo::query()
                ->forUser(auth()->id())
                ->with(['subtasks', 'attachments'])
                ->where('title', 'like', "%{$this->search}%")
                ->orderByDesc('created_at')
                ->paginate(10),
        ]);
    }

    public function toggle(Todo $todo): void
    {
        if ($todo->user_id !== auth()->id()) {
            return;
        }

        $todo->update(['completed' => ! $todo->completed]);
    }

    public function toggleSubtask(Subtask $subtask): void
    {
        $todo = $subtask->todo;

        if ($todo->user_id !== auth()->id()) {
            return;
        }

        $subtask->update(['completed' => ! $subtask->completed]);
    }

    public function delete(Todo $todo): void
    {
        if ($todo->user_id !== auth()->id()) {
            return;
        }

        $todo->delete();
        Flux::toast(variant: 'success', text: __('Todo deleted successfully.'));
    }
}
