<x-layouts::app :title="__('Users')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        <div class="flex items-center justify-between">
            <flux:heading size="xl">{{ __('Users') }}</flux:heading>
            <flux:button variant="primary" href="{{ route('users.create') }}" wire:navigate>
                {{ __('Add User') }}
            </flux:button>
        </div>

        <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="p-4">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    :label="__('Search')"
                    placeholder="{{ __('Search by name or email...') }}"
                    icon="magnifying-glass"
                />
            </div>

            <flux:table>
                <flux:table.columns>
                    <flux:table.column>{{ __('Name') }}</flux:table.column>
                    <flux:table.column>{{ __('Email') }}</flux:table.column>
                    <flux:table.column>{{ __('Created') }}</flux:table.column>
                    <flux:table.column class="text-right">{{ __('Actions') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($users as $user)
                        <flux:table.row>
                            <flux:table.cell>
                                <div class="flex items-center gap-3">
                                    <flux:avatar :name="$user->name" :initials="$user->initials()" />
                                    <span class="font-medium">{{ $user->name }}</span>
                                </div>
                            </flux:table.cell>
                            <flux:table.cell>{{ $user->email }}</flux:table.cell>
                            <flux:table.cell>{{ $user->created_at->diffForHumans() }}</flux:table.cell>
                            <flux:table.cell class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button variant="ghost" size="sm" href="{{ route('users.edit', $user->id) }}" wire:navigate>
                                        {{ __('Edit') }}
                                    </flux:button>
                                    <flux:button variant="ghost" size="sm" wire:click="delete({{ $user->id }})" x-data x-on:click="$dispatch('confirm-delete', { id: {{ $user->id }} })">
                                        {{ __('Delete') }}
                                    </flux:button>
                                </div>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="4" class="text-center">
                                <flux:text>{{ __('No users found.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="border-t border-neutral-200 p-4 dark:border-neutral-700">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-layouts::app>
