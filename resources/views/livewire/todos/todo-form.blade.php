<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <flux:heading size="xl">{{ $todoId ? __('Edit Todo') : __('Create Todo') }}</flux:heading>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <form wire:submit="save" class="p-6">
            <div class="w-full max-w-2xl space-y-6">
                <flux:input
                    wire:model="title"
                    :label="__('Title')"
                    type="text"
                    required
                    autofocus
                />

                <flux:input
                    wire:model="description"
                    :label="__('Description')"
                    type="text"
                />

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input
                        wire:model="due_date"
                        :label="__('Due Date')"
                        type="date"
                    />

                    <flux:input
                        wire:model="reminder_date"
                        :label="__('Reminder Date')"
                        type="datetime-local"
                    />
                </div>

                <flux:select wire:model="repeat_type" :label="__('Repeat')">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach ($repeatTypes as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="classification" :label="__('Classification')">
                    <flux:select.option value="">{{ __('None') }}</flux:select.option>
                    @foreach ($classificationTypes as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>

                {{-- Subtasks --}}
                <div class="space-y-3">
                    <flux:heading size="sm">{{ __('Subtasks') }}</flux:heading>

                    @foreach ($subtasks as $index => $subtask)
                        <div class="flex items-center gap-3" wire:key="subtask-{{ $index }}">
                            <input
                                type="checkbox"
                                {{ $subtask['completed'] ? 'checked' : '' }}
                                wire:change="toggleSubtask({{ $index }})"
                                class="size-4 rounded border-neutral-300 text-blue-600 focus:ring-blue-500"
                            />
                            <span class="flex-1 {{ $subtask['completed'] ? 'line-through text-neutral-400' : '' }}">
                                {{ $subtask['title'] }}
                            </span>
                            <flux:button icon="trash" variant="ghost" size="sm" wire:click="removeSubtask({{ $index }})" aria-label="{{ __('Remove') }}" />
                        </div>
                    @endforeach

                    <div class="flex items-center gap-2">
                        <flux:input
                            wire:model="newSubtaskTitle"
                            placeholder="{{ __('Add a subtask...') }}"
                            type="text"
                            class="flex-1"
                            wire:keydown.enter.prevent="addSubtask"
                        />
                        <flux:button icon="plus" variant="outline" size="sm" wire:click="addSubtask" />
                    </div>
                </div>

                {{-- Notes --}}
                <div class="space-y-2">
                    <flux:heading size="sm">{{ __('Notes') }}</flux:heading>
                    <textarea
                        wire:model="notes"
                        rows="4"
                        class="w-full rounded-lg border border-neutral-300 bg-white px-3 py-2 text-sm shadow-sm placeholder:text-neutral-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-neutral-600 dark:bg-neutral-800 dark:text-neutral-100"
                        placeholder="{{ __('Add notes...') }}"
                    ></textarea>
                </div>

                {{-- Attachments --}}
                <div class="space-y-3">
                    <flux:heading size="sm">{{ __('Attachments') }}</flux:heading>

                    @if ($todoId)
                        @php
                            $existingAttachments = \App\Models\Attachment::where('todo_id', $todoId)->get()->filter(fn ($a) => ! in_array($a->id, $removedAttachmentIds));
                        @endphp

                        @foreach ($existingAttachments as $attachment)
                            <div class="flex items-center gap-3 rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-600" wire:key="attachment-{{ $attachment->id }}">
                                <flux:icon name="document" class="size-5 text-neutral-400" />
                                <span class="flex-1 text-sm">{{ $attachment->filename }}</span>
                                <span class="text-xs text-neutral-400">{{ $attachment->formattedSize() }}</span>
                                <flux:button icon="trash" variant="ghost" size="sm" wire:click="removeAttachment({{ $attachment->id }})" aria-label="{{ __('Remove') }}" />
                            </div>
                        @endforeach
                    @endif

                    @if (count($newAttachments) > 0)
                        @foreach ($newAttachments as $index => $file)
                            <div class="flex items-center gap-3 rounded-lg border border-neutral-200 px-3 py-2 dark:border-neutral-600" wire:key="new-attachment-{{ $index }}">
                                <flux:icon name="document" class="size-5 text-neutral-400" />
                                <span class="flex-1 text-sm">{{ $file->getClientOriginalName() }}</span>
                                <span class="text-xs text-neutral-400">{{ round($file->getSize() / 1024, 1) }} KB</span>
                            </div>
                        @endforeach
                    @endif

                    <div>
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-neutral-300 px-4 py-3 text-sm text-neutral-500 hover:border-blue-400 hover:text-blue-500 dark:border-neutral-600 dark:text-neutral-400">
                            <flux:icon name="arrow-up-tray" class="size-4" />
                            {{ __('Upload files') }}
                            <input type="file" multiple wire:model="newAttachments" class="hidden" />
                        </label>
                    </div>

                    @if (count($newAttachments) > 0)
                        @foreach ($newAttachments as $file)
                            @if ($file->isPreviewable())
                                <div wire:key="preview-{{ $file->getClientOriginalName() }}">
                                    <img src="{{ $file->temporaryUrl() }}" class="max-h-32 rounded-lg border border-neutral-200 dark:border-neutral-600" />
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>

                @if ($todoId)
                    <flux:checkbox
                        wire:model="completed"
                        :label="__('Completed')"
                    />
                @endif

                <div class="flex items-center gap-4 pt-4">
                    <flux:button variant="primary" type="submit">
                        {{ $todoId ? __('Update') : __('Create') }}
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('todos.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</div>
