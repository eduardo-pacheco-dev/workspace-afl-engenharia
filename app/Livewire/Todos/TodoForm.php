<?php

namespace App\Livewire\Todos;

use App\Models\Subtask;
use App\Models\Todo;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('layouts.app.sidebar')]
#[Title('Todo Form')]
class TodoForm extends Component
{
    use WithFileUploads;

    public ?int $todoId = null;

    public string $title = '';

    public string $description = '';

    public bool $completed = false;

    public ?string $due_date = null;

    public ?string $reminder_date = null;

    public ?string $repeat_type = null;

    public string $notes = '';

    public ?string $classification = null;

    /** @var array<int, array{title: string, completed: bool}> */
    public array $subtasks = [];

    public string $newSubtaskTitle = '';

    /** @var array<int, string> */
    public array $removedSubtaskIds = [];

    /** @var TemporaryUploadedFile[] */
    public array $newAttachments = [];

    /** @var array<int, int> */
    public array $removedAttachmentIds = [];

    public function mount(?int $id = null): void
    {
        if ($id) {
            $todo = Todo::where('user_id', auth()->id())->with(['subtasks', 'attachments'])->findOrFail($id);
            $this->todoId = $todo->id;
            $this->title = $todo->title;
            $this->description = $todo->description ?? '';
            $this->completed = $todo->completed;
            $this->due_date = $todo->due_date?->format('Y-m-d');
            $this->reminder_date = $todo->reminder_date?->format('Y-m-d\TH:i');
            $this->repeat_type = $todo->repeat_type;
            $this->notes = $todo->notes ?? '';
            $this->classification = $todo->classification;
            $this->subtasks = $todo->subtasks->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'completed' => $s->completed,
            ])->toArray();
        }
    }

    public function addSubtask(): void
    {
        $this->validate(['newSubtaskTitle' => ['required', 'string', 'max:255']]);

        $this->subtasks[] = [
            'title' => $this->newSubtaskTitle,
            'completed' => false,
        ];

        $this->newSubtaskTitle = '';
    }

    public function removeSubtask(int $index): void
    {
        $subtask = $this->subtasks[$index] ?? null;

        if ($subtask && isset($subtask['id'])) {
            $this->removedSubtaskIds[] = $subtask['id'];
        }

        unset($this->subtasks[$index]);
        $this->subtasks = array_values($this->subtasks);
    }

    public function toggleSubtask(int $index): void
    {
        if (isset($this->subtasks[$index])) {
            $this->subtasks[$index]['completed'] = ! $this->subtasks[$index]['completed'];
        }
    }

    public function removeAttachment(int $attachmentId): void
    {
        $this->removedAttachmentIds[] = $attachmentId;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'completed' => ['boolean'],
            'due_date' => ['nullable', 'date'],
            'reminder_date' => ['nullable', 'date'],
            'repeat_type' => ['nullable', 'in:daily,weekly,monthly,yearly'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'classification' => ['nullable', 'in:low,medium,high'],
            'newAttachments.*' => ['file', 'max:10240'],
        ]);

        if ($this->todoId) {
            $todo = Todo::where('user_id', auth()->id())->findOrFail($this->todoId);
            $todo->update($validated);
            Flux::toast(variant: 'success', text: __('Todo updated successfully.'));
        } else {
            $todo = Todo::create([
                ...$validated,
                'user_id' => auth()->id(),
            ]);
            Flux::toast(variant: 'success', text: __('Todo created successfully.'));
        }

        foreach ($this->removedSubtaskIds as $subtaskId) {
            Subtask::where('todo_id', $todo->id)->where('id', $subtaskId)->delete();
        }

        foreach ($this->subtasks as $index => $subtaskData) {
            if (isset($subtaskData['id'])) {
                Subtask::where('todo_id', $todo->id)->where('id', $subtaskData['id'])->update([
                    'title' => $subtaskData['title'],
                    'completed' => $subtaskData['completed'],
                    'sort_order' => $index,
                ]);
            } else {
                $todo->subtasks()->create([
                    'title' => $subtaskData['title'],
                    'completed' => $subtaskData['completed'],
                    'sort_order' => $index,
                ]);
            }
        }

        foreach ($this->removedAttachmentIds as $attachmentId) {
            $attachment = $todo->attachments()->find($attachmentId);
            if ($attachment) {
                \Storage::disk('public')->delete($attachment->path);
                $attachment->delete();
            }
        }

        foreach ($this->newAttachments as $file) {
            $path = $file->store('todos', 'public');
            $todo->attachments()->create([
                'filename' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

        $this->redirectRoute('todos.index');
    }

    public function render()
    {
        return view('livewire.todos.todo-form', [
            'repeatTypes' => Todo::repeatTypes(),
            'classificationTypes' => Todo::classificationTypes(),
        ]);
    }
}
