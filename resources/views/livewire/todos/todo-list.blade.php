<div class="flex h-full w-full flex-1 flex-col rounded-xl p-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Todos') }}</flux:heading>
        <flux:button variant="primary" href="{{ route('todos.create') }}" wire:navigate>
            {{ __('Add Todo') }}
        </flux:button>
    </div>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="border-b border-neutral-200 p-5 dark:border-neutral-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search todos...') }}"
                icon="magnifying-glass"
            />
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column class="w-12 py-4"></flux:table.column>
                <flux:table.column class="px-8 py-4">{{ __('Title') }}</flux:table.column>
                <flux:table.column class="px-8 py-4">{{ __('Due') }}</flux:table.column>
                <flux:table.column class="px-8 py-4">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="px-8 py-4">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($todos as $todo)
                    <flux:table.row>
                        <flux:table.cell class="py-5 pl-5">
                            <input
                                type="checkbox"
                                {{ $todo->completed ? 'checked' : '' }}
                                wire:change="toggle({{ $todo->id }})"
                                class="size-4 rounded border-neutral-300 text-blue-600 focus:ring-blue-500"
                            />
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            <span class="{{ $todo->completed ? 'line-through text-neutral-400 dark:text-neutral-500' : 'font-medium' }}">
                                {{ $todo->title }}
                            </span>
                            @if ($todo->description)
                                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ Str::limit($todo->description, 80) }}</p>
                            @endif
                            <div class="mt-1 flex items-center gap-2">
                                @if ($todo->repeat_type)
                                    <flux:badge size="sm" color="blue">{{ __('Repeat: :type', ['type' => $todo->repeat_type]) }}</flux:badge>
                                @endif
                                @if ($todo->attachments->count() > 0)
                                    <flux:badge size="sm" color="neutral">{{ __(':count file(s)', ['count' => $todo->attachments->count()]) }}</flux:badge>
                                @endif
                            </div>
                            @if ($todo->subtasks->count() > 0)
                                <div class="mt-2 space-y-1">
                                    @foreach ($todo->subtasks->take(3) as $subtask)
                                        <div class="flex items-center gap-2 text-xs">
                                            <input
                                                type="checkbox"
                                                {{ $subtask->completed ? 'checked' : '' }}
                                                wire:change="toggleSubtask({{ $subtask->id }})"
                                                class="size-3 rounded border-neutral-300 text-blue-600 focus:ring-blue-500"
                                            />
                                            <span class="{{ $subtask->completed ? 'line-through text-neutral-400' : 'text-neutral-600 dark:text-neutral-300' }}">
                                                {{ $subtask->title }}
                                            </span>
                                        </div>
                                    @endforeach
                                    @if ($todo->subtasks->count() > 3)
                                        <span class="text-xs text-neutral-400">{{ __('+ :count more', ['count' => $todo->subtasks->count() - 3]) }}</span>
                                    @endif
                                </div>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            @if ($todo->due_date)
                                @php
                                    $isOverdue = $todo->due_date->isPast() && ! $todo->completed;
                                @endphp
                                <span class="text-sm {{ $isOverdue ? 'text-red-500 font-medium' : 'text-neutral-600 dark:text-neutral-300' }}">
                                    {{ $todo->due_date->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-sm text-neutral-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            @if ($todo->completed)
                                <flux:badge color="green">{{ __('Done') }}</flux:badge>
                            @elseif ($todo->due_date && $todo->due_date->isPast())
                                <flux:badge color="red">{{ __('Overdue') }}</flux:badge>
                            @else
                                <flux:badge color="yellow">{{ __('Pending') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            <div class="flex items-center gap-2">
                                <flux:button icon="pencil-square" variant="ghost" size="sm" href="{{ route('todos.edit', $todo->id) }}" wire:navigate aria-label="{{ __('Edit') }}" />
                                <flux:button icon="trash" variant="ghost" size="sm" wire:click="confirmDelete({{ $todo->id }})" aria-label="{{ __('Delete') }}" />
                            </div>
                        </flux:table.row>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="5" class="px-6 py-12 text-center">
                            <flux:text>{{ __('No todos found.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="border-t border-neutral-200 px-6 py-4 dark:border-neutral-700">
            {{ $todos->links() }}
        </div>
    </div>

    <flux:modal name="confirm-todo-deletion" wire:model="showDeleteModal" focusable class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Are you sure you want to delete this todo?') }}</flux:heading>
                <flux:subheading>
                    {{ __('This action cannot be undone. The todo and all its subtasks and attachments will be permanently deleted.') }}
                </flux:subheading>
            </div>

            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled" wire:click="cancelDelete">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
