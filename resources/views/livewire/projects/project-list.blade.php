<div class="flex h-full w-full flex-1 flex-col rounded-xl p-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Projects') }}</flux:heading>
        <flux:button variant="primary" href="{{ route('projects.create') }}" wire:navigate>
            {{ __('Add Project') }}
        </flux:button>
    </div>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="border-b border-neutral-200 p-5 dark:border-neutral-700">
            <div class="flex flex-col gap-4 sm:flex-row">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name, operator, address or responsible...') }}" icon="magnifying-glass" class="flex-1" />
                <flux:select wire:model.live="statusFilter" class="w-full sm:w-48">
                    <flux:select.option value="all">{{ __('All') }}</flux:select.option>
                    @foreach ($statuses as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="typeFilter" class="w-full sm:w-48">
                    <flux:select.option value="all">{{ __('All Types') }}</flux:select.option>
                    @foreach ($types as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column class="pr-8 pl-12 py-4 text-center">{{ __('Name') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Type') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Operator') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Address') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Responsible') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($projects as $project)
                    <flux:table.row>
                        <flux:table.cell class="px-8 py-5">
                            <a href="{{ route('projects.show', $project->id) }}" wire:navigate class="font-medium text-blue-600 hover:underline dark:text-blue-400">{{ $project->name }}</a>
                            @if ($project->description)
                                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ Str::limit($project->description, 60) }}</p>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            @if ($project->type)
                                <flux:badge size="sm">{{ $types[$project->type] ?? $project->type }}</flux:badge>
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            @if ($project->operator)
                                <flux:badge size="sm">{{ $operators[$project->operator] ?? $project->operator }}</flux:badge>
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">{{ $project->address ?? '-' }}</flux:table.cell>
                        <flux:table.cell class="px-8 py-5">{{ $project->responsible ?? '-' }}</flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            @if ($project->status === 'active')
                                <flux:badge color="green" size="sm">{{ __('Ativo') }}</flux:badge>
                            @elseif ($project->status === 'inactive')
                                <flux:badge color="red" size="sm">{{ __('Inativo') }}</flux:badge>
                            @elseif ($project->status === 'completed')
                                <flux:badge color="blue" size="sm">{{ __('Concluído') }}</flux:badge>
                            @elseif ($project->status === 'suspended')
                                <flux:badge color="yellow" size="sm">{{ __('Suspenso') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            <div class="flex items-center gap-2">
                                <flux:button icon="eye" variant="ghost" size="sm" href="{{ route('projects.show', $project->id) }}" wire:navigate aria-label="{{ __('View') }}" />
                                <flux:button icon="pencil-square" variant="ghost" size="sm" href="{{ route('projects.edit', $project->id) }}" wire:navigate aria-label="{{ __('Edit') }}" />
                                <flux:button icon="trash" variant="ghost" size="sm" wire:click="confirmDelete({{ $project->id }})" aria-label="{{ __('Delete') }}" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="7" class="px-6 py-12 text-center">
                            <flux:text>{{ __('No projects found.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="border-t border-neutral-200 px-6 py-4 dark:border-neutral-700">
            {{ $projects->links() }}
        </div>
    </div>

    <flux:modal name="confirm-project-deletion" wire:model="showDeleteModal" focusable class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Are you sure you want to delete this project?') }}</flux:heading>
                <flux:subheading>{{ __('This action cannot be undone.') }}</flux:subheading>
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
