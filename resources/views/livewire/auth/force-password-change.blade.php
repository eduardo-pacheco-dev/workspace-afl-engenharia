<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-8">
    <flux:heading size="xl">{{ __('Change Password') }}</flux:heading>
    <flux:text>{{ __('You must change your password before continuing.') }}</flux:text>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <form wire:submit="save" class="p-6">
            <div class="w-full max-w-lg space-y-6">
                <flux:input
                    wire:model="password"
                    :label="__('New Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                />

                <flux:input
                    wire:model="password_confirmation"
                    :label="__('Confirm Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                />

                <div class="flex items-center gap-4 pt-4">
                    <flux:button variant="primary" type="submit">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</div>
