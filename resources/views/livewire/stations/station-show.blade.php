<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Station Details') }}</flux:heading>
        <div class="flex items-center gap-2">
            <flux:button variant="primary" href="{{ route('stations.edit', $station->id) }}" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
            <flux:button variant="ghost" href="{{ route('stations.index') }}" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="p-6">
            <div class="w-full max-w-2xl space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Site ID') }}</flux:text>
                        <p class="mt-1 text-base">{{ $station->site_id }}</p>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('End ID') }}</flux:text>
                        <p class="mt-1 text-base">{{ $station->end_id ?? '-' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Operadora') }}</flux:text>
                        <div class="mt-1">
                            @if ($station->operadora)
                                <flux:badge size="sm">{{ $station->operadora }}</flux:badge>
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Description') }}</flux:text>
                        <p class="mt-1 text-base">{{ $station->description ?? '-' }}</p>
                    </div>
                </div>

                <div>
                    <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Address') }}</flux:text>
                    <p class="mt-1 text-base">{{ $station->address }}</p>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Latitude') }}</flux:text>
                        <p class="mt-1 text-base">{{ $station->latitude ?? '-' }}</p>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Longitude') }}</flux:text>
                        <p class="mt-1 text-base">{{ $station->longitude ?? '-' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Status') }}</flux:text>
                        <div class="mt-1">
                            @if ($station->status === 'active')
                                <flux:badge color="green" size="sm">{{ __('Ativa') }}</flux:badge>
                            @elseif ($station->status === 'inactive')
                                <flux:badge color="red" size="sm">{{ __('Inativa') }}</flux:badge>
                            @elseif ($station->status === 'maintenance')
                                <flux:badge color="yellow" size="sm">{{ __('Manutenção') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Type') }}</flux:text>
                        <p class="mt-1 text-base">{{ $types[$station->type] ?? '-' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Responsible') }}</flux:text>
                        <p class="mt-1 text-base">{{ $station->responsible ?? '-' }}</p>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Phone') }}</flux:text>
                        <p class="mt-1 text-base">{{ $station->phone ?? '-' }}</p>
                    </div>
                </div>

                <div class="border-t border-neutral-200 pt-6 dark:border-neutral-700">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Created at') }}</flux:text>
                            <p class="mt-1 text-base">{{ $station->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Updated at') }}</flux:text>
                            <p class="mt-1 text-base">{{ $station->updated_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="p-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Attachments') }}</flux:heading>
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ __(':count file(s)', ['count' => $station->attachments->count()]) }}
                    </flux:text>
                </div>

                @if ($station->attachments->count() > 0)
                    <div class="space-y-2">
                        @foreach ($station->attachments as $attachment)
                            @php
                                $isImage = str_starts_with($attachment->mime_type, 'image/');
                                $isPdf = $attachment->mime_type === 'application/pdf';
                                $url = Storage::disk('public')->url($attachment->path);
                            @endphp
                            <div class="flex items-center gap-3 rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                                @if ($isImage)
                                    <img src="{{ $url }}" alt="{{ $attachment->filename }}" class="size-10 rounded object-cover" />
                                @elseif ($isPdf)
                                    <flux:icon name="document-text" class="size-5 text-red-500" />
                                @else
                                    <flux:icon name="document" class="size-5 text-neutral-400" />
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ $attachment->filename }}</p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ $attachment->formattedSize() }}</p>
                                </div>
                                @if ($isImage || $isPdf)
                                    <flux:button icon="eye" variant="ghost" size="sm" wire:click="openPreview('{{ $url }}', '{{ $attachment->filename }}', '{{ $attachment->mime_type }}')" aria-label="{{ __('Preview') }}" />
                                @endif
                                <flux:button icon="trash" variant="ghost" size="sm" wire:click="removeAttachment({{ $attachment->id }})" wire:confirm="{{ __('Are you sure you want to delete this attachment?') }}" aria-label="{{ __('Delete') }}" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('No attachments yet.') }}</p>
                @endif

                <div>
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-dashed border-neutral-300 p-4 text-sm text-neutral-500 transition hover:border-neutral-400 hover:text-neutral-700 dark:border-neutral-600 dark:text-neutral-400 dark:hover:border-neutral-500 dark:hover:text-neutral-300">
                        <flux:icon name="arrow-up-tray" class="size-4" />
                        {{ __('Select files') }}
                        <input type="file" multiple wire:model="newAttachments" class="hidden" />
                    </label>
                </div>

                @if (count($newAttachments) > 0)
                    <div class="space-y-2">
                        @foreach ($newAttachments as $index => $file)
                            @php
                                $isImage = str_starts_with($file->getMimeType(), 'image/');
                                $isPdf = $file->getMimeType() === 'application/pdf';
                            @endphp
                            <div class="flex items-center gap-3 rounded-lg border border-neutral-200 p-3 dark:border-neutral-700">
                                @if ($isImage)
                                    <img src="{{ $file->temporaryUrl() }}" alt="{{ $file->getClientOriginalName() }}" class="size-10 rounded object-cover" />
                                @elseif ($isPdf)
                                    <flux:icon name="document-text" class="size-5 text-red-500" />
                                @else
                                    <flux:icon name="document" class="size-5 text-neutral-400" />
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate">{{ $file->getClientOriginalName() }}</p>
                                    <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ round($file->getSize() / 1024, 1) }} KB</p>
                                </div>
                                @if ($isImage || $isPdf)
                                    <flux:button icon="eye" variant="ghost" size="sm" wire:click="openPreview('{{ $file->temporaryUrl() }}', '{{ $file->getClientOriginalName() }}', '{{ $file->getMimeType() }}')" aria-label="{{ __('Preview') }}" />
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div>
                        <flux:button variant="primary" wire:click="saveAttachments" size="sm">
                            {{ __('Upload') }}
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="p-6">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <flux:heading size="lg">{{ __('Comments') }}</flux:heading>
                    <flux:text class="text-sm text-neutral-500 dark:text-neutral-400">
                        {{ __(':count comment(s)', ['count' => $station->comments->count()]) }}
                    </flux:text>
                </div>

                <div>
                    <form wire:submit="addComment">
                        <flux:textarea wire:model="newComment" :placeholder="__('Write a comment...')" rows="3" />
                        <div class="mt-2 flex justify-end">
                            <flux:button variant="primary" type="submit" size="sm">
                                {{ __('Post') }}
                            </flux:button>
                        </div>
                    </form>
                </div>

                @if ($station->comments->count() > 0)
                    <div class="space-y-3">
                        @foreach ($station->comments->sortByDesc('created_at') as $comment)
                            <div class="rounded-lg border border-neutral-200 p-4 dark:border-neutral-700">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-2">
                                        <flux:avatar :initials="substr($comment->user->name ?? 'U', 0, 2)" class="size-8" />
                                        <div>
                                            <p class="text-sm font-medium">{{ $comment->user->name ?? __('Unknown') }}</p>
                                            <p class="text-xs text-neutral-500 dark:text-neutral-400">{{ $comment->created_at->diffForHumans() }}</p>
                                        </div>
                                    </div>
                                    @if ($comment->user_id === auth()->id())
                                        <flux:button icon="trash" variant="ghost" size="sm" wire:click="deleteComment({{ $comment->id }})" wire:confirm="{{ __('Are you sure you want to delete this comment?') }}" aria-label="{{ __('Delete') }}" />
                                    @endif
                                </div>
                                <p class="mt-2 text-sm whitespace-pre-wrap">{{ $comment->body }}</p>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-neutral-500 dark:text-neutral-400">{{ __('No comments yet.') }}</p>
                @endif
            </div>
        </div>
    </div>

    <flux:modal name="preview-attachment" wire:model="showPreviewModal" class="max-w-[66vw]">
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ $previewFilename }}</flux:heading>
                <flux:modal.close>
                    <flux:button icon="x-mark" variant="ghost" size="sm" />
                </flux:modal.close>
            </div>
            <div>
                @if (str_starts_with($previewMime, 'image/'))
                    <img src="{{ $previewUrl }}" alt="{{ $previewFilename }}" class="max-h-[66vh] w-full rounded-lg object-contain" />
                @elseif ($previewMime === 'application/pdf')
                    <iframe src="{{ $previewUrl }}" class="h-[66vh] w-full rounded-lg border-0"></iframe>
                @endif
            </div>
        </div>
    </flux:modal>
</div>
