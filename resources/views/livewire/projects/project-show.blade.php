<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Project Details') }}</flux:heading>
        <div class="flex items-center gap-2">
            <flux:button variant="primary" href="{{ route('projects.edit', $project->id) }}" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
            <flux:button variant="ghost" href="{{ route('projects.index') }}" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Info Card --}}
        <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            <flux:heading size="lg" class="mb-4">{{ __('Information') }}</flux:heading>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Name') }}</dt>
                    <dd class="mt-1">{{ $project->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Type') }}</dt>
                    <dd class="mt-1">
                        @if ($project->type)
                            <flux:badge>{{ $types[$project->type] ?? $project->type }}</flux:badge>
                        @else
                            <span class="text-neutral-400">-</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Status') }}</dt>
                    <dd class="mt-1">
                        @if ($project->status === 'active')
                            <flux:badge color="green">{{ __('Ativo') }}</flux:badge>
                        @elseif ($project->status === 'inactive')
                            <flux:badge color="red">{{ __('Inativo') }}</flux:badge>
                        @elseif ($project->status === 'completed')
                            <flux:badge color="blue">{{ __('Concluído') }}</flux:badge>
                        @elseif ($project->status === 'suspended')
                            <flux:badge color="yellow">{{ __('Suspenso') }}</flux:badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Operator') }}</dt>
                    <dd class="mt-1">
                        @if ($project->operator)
                            <flux:badge>{{ $operators[$project->operator] ?? $project->operator }}</flux:badge>
                        @else
                            <span class="text-neutral-400">-</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Description') }}</dt>
                    <dd class="mt-1">{{ $project->description ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Address') }}</dt>
                    <dd class="mt-1">{{ $project->address ?? '-' }}</dd>
                </div>
                @if ($project->latitude && $project->longitude)
                    <div>
                        <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Coordinates') }}</dt>
                        <dd class="mt-1">{{ $project->latitude }}, {{ $project->longitude }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Details Card --}}
        <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
            <flux:heading size="lg" class="mb-4">{{ __('Details') }}</flux:heading>
            <dl class="space-y-4">
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Responsible') }}</dt>
                    <dd class="mt-1">{{ $project->responsible ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Phone') }}</dt>
                    <dd class="mt-1">{{ $project->phone ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Start Date') }}</dt>
                    <dd class="mt-1">{{ $project->start_date?->format('d/m/Y') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('End Date') }}</dt>
                    <dd class="mt-1">{{ $project->end_date?->format('d/m/Y') ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Notes') }}</dt>
                    <dd class="mt-1 whitespace-pre-line">{{ $project->notes ?? '-' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Attachments --}}
    <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
        <flux:heading size="lg" class="mb-4">{{ __('Attachments') }}</flux:heading>
        <div class="mb-4">
            <flux:fileupload wire:model="newAttachments" multiple accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip" class="w-full">
                <flux:fileupload.dropzone>
                    <flux:icon name="arrow-up-tray" class="mx-auto size-8 text-neutral-400" />
                    <flux:text>{{ __('Drop files here or click to upload') }}</flux:text>
                    <flux:text size="sm" class="text-neutral-500">{{ __('Max 10MB per file') }}</flux:text>
                </flux:fileupload.dropzone>
            </flux:fileupload>
        </div>

        @if (!empty($newAttachments))
            <div class="mb-4 flex flex-wrap gap-2">
                @foreach ($newAttachments as $index => $attachment)
                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-3 py-1 text-sm text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        {{ $attachment->getClientOriginalName() }}
                        <button type="button" wire:click="removeUploadedAttachment({{ $index }})" class="ml-1 text-blue-600 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-100">&times;</button>
                    </span>
                @endforeach
                <flux:button variant="primary" size="sm" wire:click="saveAttachments" wire:loading.attr="disabled">
                    {{ __('Upload') }}
                </flux:button>
            </div>
        @endif

        @if ($project->attachments->count())
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($project->attachments as $attachment)
                    <div class="flex items-center justify-between rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                        <div class="flex items-center gap-3">
                            <flux:icon name="document" class="size-8 text-neutral-400" />
                            <div>
                                <p class="text-sm font-medium">{{ $attachment->filename }}</p>
                                <p class="text-xs text-neutral-500">{{ number_format($attachment->size / 1024, 1) }} KB</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1">
                            <flux:button icon="eye" variant="ghost" size="sm" wire:click="openPreview('{{ Storage::url($attachment->path) }}', '{{ $attachment->filename }}', '{{ $attachment->mime_type }}')" />
                            <flux:button icon="trash" variant="ghost" size="sm" wire:click="removeAttachment({{ $attachment->id }})" />
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-neutral-500 dark:text-neutral-400">{{ __('No attachments yet.') }}</p>
        @endif
    </div>

    {{-- Comments --}}
    <div class="rounded-xl border border-neutral-200 p-6 dark:border-neutral-700">
        <flux:heading size="lg" class="mb-4">{{ __('Comments') }}</flux:heading>

        <div class="mb-6">
            <flux:textarea wire:model="newComment" placeholder="{{ __('Add a comment...') }}" rows="3" />
            <div class="mt-2 flex justify-end">
                <flux:button variant="primary" size="sm" wire:click="addComment" wire:loading.attr="disabled">
                    {{ __('Add Comment') }}
                </flux:button>
            </div>
        </div>

        @if ($project->comments->count())
            <div class="space-y-4">
                @foreach ($project->comments->sortByDesc('created_at') as $comment)
                    <div class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ $comment->user->name }}</span>
                                <span class="text-sm text-neutral-500 dark:text-neutral-400">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            @if ($comment->user_id === auth()->id())
                                <flux:button icon="trash" variant="ghost" size="sm" wire:click="deleteComment({{ $comment->id }})" />
                            @endif
                        </div>
                        <p class="mt-2 whitespace-pre-line">{{ $comment->body }}</p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-neutral-500 dark:text-neutral-400">{{ __('No comments yet.') }}</p>
        @endif
    </div>

    {{-- Preview Modal --}}
    <flux:modal name="preview-modal" wire:model="showPreviewModal" class="max-w-4xl">
        <div class="space-y-4">
            <flux:heading size="lg">{{ $previewFilename }}</flux:heading>
            @if (str_starts_with($previewMime, 'image/'))
                <img src="{{ $previewUrl }}" alt="{{ $previewFilename }}" class="w-full rounded-lg" />
            @elseif ($previewMime === 'application/pdf')
                <iframe src="{{ $previewUrl }}" class="h-[600px] w-full rounded-lg"></iframe>
            @else
                <p>{{ __('Preview not available for this file type.') }}</p>
                <flux:button variant="primary" href="{{ $previewUrl }}" target="_blank">{{ __('Download') }}</flux:button>
            @endif
        </div>
    </flux:modal>
</div>
