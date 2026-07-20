<div x-data="passwordStrength()" data-translations="{{ json_encode(['ep' => __('Enter a password'), 'vw' => __('Very weak'), 'wk' => __('Weak'), 'fa' => __('Fair'), 'st' => __('Strong'), 'vs' => __('Very strong'), 'mc' => __('At least 8 characters'), 'up' => __('One uppercase letter'), 'lo' => __('One lowercase letter'), 'nu' => __('One number'), 'sp' => __('One special character (!@#$%^&*)')]) }}">
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

        <button
            type="submit"
            class="w-full inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium bg-indigo-600 text-white hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition"
            x-bind:disabled="level < 3"
        >
            {{ __('Change Password and Continue') }}
        </button>
    </form>
</div>

<script>
    document.addEventListener('alpine:init', function() {
        Alpine.data('passwordStrength', function() {
            var el = document.querySelector('[data-translations]');
            var t = JSON.parse(el.dataset.translations);
            return {
                level: 0,
                label: t.ep,
                rules: [
                    { label: t.mc, met: false },
                    { label: t.up, met: false },
                    { label: t.lo, met: false },
                    { label: t.nu, met: false },
                    { label: t.sp, met: false },
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
                        this.label = t.ep;
                    } else if (score <= 1) {
                        this.level = 1;
                        this.label = t.vw;
                    } else if (score === 2) {
                        this.level = 2;
                        this.label = t.wk;
                    } else if (score === 3) {
                        this.level = 3;
                        this.label = t.fa;
                    } else {
                        this.level = 4;
                        this.label = score === 4 ? t.st : t.vs;
                    }
                }
            };
        });
    });
</script>
