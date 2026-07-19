<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <flux:heading size="xl">{{ $userId ? __('Edit User') : __('Create User') }}</flux:heading>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <form wire:submit="save" class="p-6">
            <div class="w-full max-w-lg space-y-6">
                <flux:input
                    wire:model="name"
                    :label="__('Name')"
                    type="text"
                    required
                    autofocus
                    autocomplete="name"
                />

                <flux:input
                    wire:model="email"
                    :label="__('Email')"
                    type="email"
                    required
                    autocomplete="email"
                />

                <flux:input
                    wire:model="password"
                    :label="$userId ? __('New Password (leave blank to keep current)') : __('Password')"
                    type="password"
                    autocomplete="new-password"
                />

                @unless ($userId)
                    <flux:checkbox
                        wire:model="sendWelcomeEmail"
                        :label="__('Send welcome email to user')"
                    />
                @endunless

                <div class="flex items-center gap-4 pt-4">
                    <flux:button variant="primary" type="submit">
                        {{ $userId ? __('Update') : __('Create') }}
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('users.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</div>
