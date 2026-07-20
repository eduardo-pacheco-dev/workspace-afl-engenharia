<div class="flex min-h-[80vh] items-center justify-center">
    <div class="w-full max-w-md">
        <div class="rounded-xl border border-neutral-200 bg-white p-8 shadow-lg dark:border-neutral-700 dark:bg-zinc-900">
            <div class="mb-6 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon name="key" class="h-8 w-8 text-amber-600 dark:text-amber-400" />
                </div>
                <flux:heading size="lg">{{ __('Password Change Required') }}</flux:heading>
                <flux:text class="mt-2">{{ __('You must change your password before accessing the admin panel.') }}</flux:text>
            </div>

            <form wire:submit="save" class="space-y-5">
                <flux:input
                    wire:model="password"
                    :label="__('New Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Enter your new password')"
                />

                <flux:input
                    wire:model="password_confirmation"
                    :label="__('Confirm Password')"
                    type="password"
                    required
                    autocomplete="new-password"
                    :placeholder="__('Confirm your new password')"
                />

                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Change Password and Continue') }}
                </flux:button>
            </form>
        </div>
    </div>
</div>
