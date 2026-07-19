<div class="flex h-full w-full flex-1 flex-col rounded-xl p-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Users') }}</flux:heading>
        <flux:button variant="primary" href="{{ route('users.create') }}" wire:navigate>
            {{ __('Add User') }}
        </flux:button>
    </div>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="border-b border-neutral-200 p-5 dark:border-neutral-700">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search by name or email...') }}"
                icon="magnifying-glass"
            />
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column class="px-6 py-4">{{ __('Name') }}</flux:table.column>
                <flux:table.column class="px-6 py-4">{{ __('Email') }}</flux:table.column>
                <flux:table.column class="px-6 py-4">{{ __('Created') }}</flux:table.column>
                <flux:table.column class="px-6 py-4 text-right">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($users as $user)
                    <flux:table.row>
                        <flux:table.cell class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <flux:avatar :name="$user->name" :initials="$user->initials()" />
                                <span class="font-medium">{{ $user->name }}</span>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="px-6 py-4">{{ $user->email }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4">{{ $user->created_at->diffForHumans() }}</flux:table.cell>
                        <flux:table.cell class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <flux:button icon="pencil-square" variant="ghost" size="sm" href="{{ route('users.edit', $user->id) }}" wire:navigate aria-label="{{ __('Edit') }}" />
                                <flux:button icon="trash" variant="ghost" size="sm" wire:click="delete({{ $user->id }})" aria-label="{{ __('Delete') }}" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="4" class="px-6 py-12 text-center">
                            <flux:text>{{ __('No users found.') }}</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="border-t border-neutral-200 px-6 py-4 dark:border-neutral-700">
            {{ $users->links() }}
        </div>
    </div>
</div>
