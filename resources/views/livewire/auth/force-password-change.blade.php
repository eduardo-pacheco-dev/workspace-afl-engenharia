<div class="flex min-h-[80vh] items-center justify-center" x-data="passwordStrength()">
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
                <div>
                    <flux:input
                        wire:model="password"
                        name="password"
                        :label="__('New Password')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Enter your new password')"
                        @input="evaluate($event.target.value)"
                    />

                    <div class="mt-3 space-y-2">
                        <div class="flex gap-1.5">
                            <div class="h-1.5 flex-1 rounded-full transition-colors duration-200"
                                 :class="level >= 1 ? (level === 1 ? 'bg-red-500' : level === 2 ? 'bg-amber-500' : level === 3 ? 'bg-yellow-400' : 'bg-green-500') : 'bg-neutral-200 dark:bg-neutral-700'"></div>
                            <div class="h-1.5 flex-1 rounded-full transition-colors duration-200"
                                 :class="level >= 2 ? (level === 2 ? 'bg-amber-500' : level === 3 ? 'bg-yellow-400' : 'bg-green-500') : 'bg-neutral-200 dark:bg-neutral-700'"></div>
                            <div class="h-1.5 flex-1 rounded-full transition-colors duration-200"
                                 :class="level >= 3 ? (level === 3 ? 'bg-yellow-400' : 'bg-green-500') : 'bg-neutral-200 dark:bg-neutral-700'"></div>
                            <div class="h-1.5 flex-1 rounded-full transition-colors duration-200"
                                 :class="level >= 4 ? 'bg-green-500' : 'bg-neutral-200 dark:bg-neutral-700'"></div>
                        </div>

                        <flux:text class="text-xs" :class="{
                            'text-red-500': level === 1,
                            'text-amber-500': level === 2,
                            'text-yellow-600': level === 3,
                            'text-green-600': level === 4,
                            'text-neutral-400': level === 0
                        }" x-text="label"></flux:text>
                    </div>

                    <div class="mt-3 space-y-1">
                        <template x-for="rule in rules" :key="rule.label">
                            <div class="flex items-center gap-2 text-xs" :class="rule.met ? 'text-green-600' : 'text-neutral-400'">
                                <flux:icon :name="rule.met ? 'check-circle' : 'x-circle'" class="h-3.5 w-3.5 shrink-0" />
                                <span x-text="rule.label"></span>
                            </div>
                        </template>
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

                <flux:button variant="primary" type="submit" class="w-full" :disabled="level < 3">
                    {{ __('Change Password and Continue') }}
                </flux:button>
            </form>
        </div>
    </div>
</div>

<script>
function passwordStrength() {
    return {
        level: 0,
        label: '',
        rules: [
            { label: '{{ __("At least 8 characters") }}', met: false },
            { label: '{{ __("One uppercase letter") }}', met: false },
            { label: '{{ __("One lowercase letter") }}', met: false },
            { label: '{{ __("One number") }}', met: false },
            { label: '{{ __("One special character (!@#$%^&*)") }}', met: false },
        ],
        evaluate(password) {
            this.rules[0].met = password.length >= 8;
            this.rules[1].met = /[A-Z]/.test(password);
            this.rules[2].met = /[a-z]/.test(password);
            this.rules[3].met = /[0-9]/.test(password);
            this.rules[4].met = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);

            const score = this.rules.filter(r => r.met).length;

            if (password.length === 0) {
                this.level = 0;
                this.label = '{{ __("Enter a password") }}';
            } else if (score <= 1) {
                this.level = 1;
                this.label = '{{ __("Very weak") }}';
            } else if (score === 2) {
                this.level = 2;
                this.label = '{{ __("Weak") }}';
            } else if (score === 3) {
                this.level = 3;
                this.label = '{{ __("Fair") }}';
            } else if (score === 4) {
                this.level = 4;
                this.label = '{{ __("Strong") }}';
            } else {
                this.level = 4;
                this.label = '{{ __("Very strong") }}';
            }
        }
    }
}
</script>
