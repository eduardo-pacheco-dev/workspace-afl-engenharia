<div>
    <div class="mb-6 text-center">
        <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
            <flux:icon name="key" class="h-8 w-8 text-amber-600 dark:text-amber-400" />
        </div>
        <flux:heading size="lg">{{ __('Password Change Required') }}</flux:heading>
        <flux:text class="mt-2">{{ __('You must change your password before accessing the admin panel.') }}</flux:text>
    </div>

    <form wire:submit="save" class="space-y-5">
        <div>
            <flux:input
                wire:model.live="password"
                :label="__('New Password')"
                type="password"
                required
                autocomplete="new-password"
                :placeholder="__('Enter your new password')"
            />

            <div class="mt-3 space-y-2">
                <div class="flex gap-1.5">
                    @for ($i = 1; $i <= 4; $i++)
                        <div class="h-1.5 flex-1 rounded-full transition-colors duration-200 {{ $level >= $i
                            ? match(true) {
                                $i <= 1 => 'bg-red-500',
                                $i <= 2 => 'bg-amber-500',
                                $i <= 3 => 'bg-yellow-400',
                                default => 'bg-green-500',
                            }
                            : 'bg-neutral-200 dark:bg-neutral-700'
                        }}"></div>
                    @endfor
                </div>

                <span class="text-xs block {{ match($level) {
                    1 => 'text-red-500',
                    2 => 'text-amber-500',
                    3 => 'text-yellow-600',
                    4 => 'text-green-600',
                    default => 'text-neutral-400',
                } }}">
                    {{ $levelLabel }}
                </span>
            </div>

            <div class="mt-3 space-y-1">
                @foreach ($rules as $rule)
                    <div class="flex items-center gap-2 text-xs {{ $rule['met'] ? 'text-green-600' : 'text-neutral-400' }}">
                        @if ($rule['met'])
                            <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10Zm-.997-6 7.071-7.071-1.414-1.414-5.657 5.657-2.829-2.829-1.414 1.414L11.003 16Z"/></svg>
                        @else
                            <svg class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10Zm1-11.414 3.293-3.293 1.414 1.414L14.414 12l3.293 3.293-1.414 1.414L13 13.414V18h-2v-4.586l-3.293 3.293-1.414-1.414L11.586 12 8.293 8.707l1.414-1.414L13 10.586V6h2v4.586Z"/></svg>
                        @endif
                        <span>{{ $rule['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirm Password')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Confirm your new password')"
        />

        <button
            type="submit"
            class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition"
            @disabled($level < 3)
        >
            {{ __('Change Password and Continue') }}
        </button>
    </form>
</div>
