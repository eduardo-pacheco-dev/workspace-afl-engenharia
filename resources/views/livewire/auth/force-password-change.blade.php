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
                        :label="__('New Password')"
                        type="password"
                        required
                        autocomplete="new-password"
                        :placeholder="__('Enter your new password')"
                        x-on:input="evaluate($event.target.value)"
                    />

                    <div class="mt-3 space-y-2">
                        <div class="flex gap-1.5">
                            <template x-for="i in 4" :key="i">
                                <div class="h-1.5 flex-1 rounded-full transition-colors duration-200"
                                     :class="level >= i
                                         ? (i <= 1 ? 'bg-red-500' : i <= 2 ? 'bg-amber-500' : i <= 3 ? 'bg-yellow-400' : 'bg-green-500')
                                         : 'bg-neutral-200 dark:bg-neutral-700'"></div>
                            </template>
                        </div>

                        <span class="text-xs block"
                              x-bind:class="{
                                  'text-red-500': level === 1,
                                  'text-amber-500': level === 2,
                                  'text-yellow-600': level === 3,
                                  'text-green-600': level === 4,
                                  'text-neutral-400': level === 0
                              }"
                              x-text="label"></span>
                    </div>

                    <div class="mt-3 space-y-1">
                        <template x-for="(rule, index) in rules" :key="index">
                            <div class="flex items-center gap-2 text-xs" :class="rule.met ? 'text-green-600' : 'text-neutral-400'">
                                <svg x-show="rule.met" class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10Zm-.997-6 7.071-7.071-1.414-1.414-5.657 5.657-2.829-2.829-1.414 1.414L11.003 16Z"/></svg>
                                <svg x-show="!rule.met" class="h-3.5 w-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10Zm1-11.414 3.293-3.293 1.414 1.414L14.414 12l3.293 3.293-1.414 1.414L13 13.414V18h-2v-4.586l-3.293 3.293-1.414-1.414L11.586 12 8.293 8.707l1.414-1.414L13 10.586V6h2v4.586Z"/></svg>
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

                <flux:button variant="primary" type="submit" class="w-full" x-bind:disabled="level < 3">
                    {{ __('Change Password and Continue') }}
                </flux:button>
            </form>
        </div>
    </div>

    <script type="application/json" id="strength-translations">
        @json([
            'enterPassword' => __('Enter a password'),
            'veryWeak' => __('Very weak'),
            'weak' => __('Weak'),
            'fair' => __('Fair'),
            'strong' => __('Strong'),
            'veryStrong' => __('Very strong'),
            'minChars' => __('At least 8 characters'),
            'uppercase' => __('One uppercase letter'),
            'lowercase' => __('One lowercase letter'),
            'number' => __('One number'),
            'special' => __('One special character (!@#$%^&*)'),
        ])
    </script>

    <script>
        document.addEventListener('alpine:init', function() {
            Alpine.data('passwordStrength', function() {
                var t = JSON.parse(document.getElementById('strength-translations').textContent);
                return {
                    level: 0,
                    label: t.enterPassword,
                    rules: [
                        { label: t.minChars, met: false },
                        { label: t.uppercase, met: false },
                        { label: t.lowercase, met: false },
                        { label: t.number, met: false },
                        { label: t.special, met: false },
                    ],
                    evaluate: function(password) {
                        this.rules[0].met = password.length >= 8;
                        this.rules[1].met = /[A-Z]/.test(password);
                        this.rules[2].met = /[a-z]/.test(password);
                        this.rules[3].met = /[0-9]/.test(password);
                        this.rules[4].met = /[!@#$%^&*()_+\-=\[\]{};':",.<>\/?]/.test(password);

                        var score = this.rules.filter(function(r) { return r.met; }).length;

                        if (password.length === 0) {
                            this.level = 0;
                            this.label = t.enterPassword;
                        } else if (score <= 1) {
                            this.level = 1;
                            this.label = t.veryWeak;
                        } else if (score === 2) {
                            this.level = 2;
                            this.label = t.weak;
                        } else if (score === 3) {
                            this.level = 3;
                            this.label = t.fair;
                        } else {
                            this.level = 4;
                            this.label = score === 4 ? t.strong : t.veryStrong;
                        }
                    }
                };
            });
        });
    </script>
</div>
