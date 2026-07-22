<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl">{{ __('Freelancer Details') }}</flux:heading>
        <div class="flex items-center gap-2">
            <flux:button variant="primary" href="{{ route('freelancers.edit', $freelancer->id) }}" wire:navigate>
                {{ __('Edit') }}
            </flux:button>
            <flux:button variant="ghost" href="{{ route('freelancers.index') }}" wire:navigate>
                {{ __('Back') }}
            </flux:button>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="p-6">
            <div class="w-full max-w-2xl space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Name') }}</flux:text>
                        <p class="mt-1 text-base">{{ $freelancer->name }}</p>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Email') }}</flux:text>
                        <p class="mt-1 text-base">{{ $freelancer->email ?? '-' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Phone') }}</flux:text>
                        <p class="mt-1 text-base">{{ $freelancer->phone ?? '-' }}</p>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('CPF/CNPJ') }}</flux:text>
                        <p class="mt-1 text-base">{{ $freelancer->cpf_cnpj ?? '-' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Specialization') }}</flux:text>
                        <div class="mt-1">
                            @if ($freelancer->specialization)
                                <flux:badge size="sm">{{ $specializations[$freelancer->specialization] ?? $freelancer->specialization }}</flux:badge>
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Status') }}</flux:text>
                        <div class="mt-1">
                            @if ($freelancer->status === 'active')
                                <flux:badge color="green" size="sm">{{ __('Ativo') }}</flux:badge>
                            @elseif ($freelancer->status === 'inactive')
                                <flux:badge color="red" size="sm">{{ __('Inativo') }}</flux:badge>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Hourly Rate') }}</flux:text>
                        <p class="mt-1 text-base">
                            @if ($freelancer->hourly_rate)
                                R$ {{ number_format($freelancer->hourly_rate, 2, ',', '.') }}
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </p>
                    </div>
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Address') }}</flux:text>
                        <p class="mt-1 text-base">{{ $freelancer->address ?? '-' }}</p>
                    </div>
                </div>

                @if ($freelancer->notes)
                    <div>
                        <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Notes') }}</flux:text>
                        <p class="mt-1 text-base whitespace-pre-wrap">{{ $freelancer->notes }}</p>
                    </div>
                @endif

                <div class="border-t border-neutral-200 pt-6 dark:border-neutral-700">
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Created at') }}</flux:text>
                            <p class="mt-1 text-base">{{ $freelancer->created_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                        <div>
                            <flux:text class="text-sm font-medium text-neutral-500 dark:text-neutral-400">{{ __('Updated at') }}</flux:text>
                            <p class="mt-1 text-base">{{ $freelancer->updated_at?->format('d/m/Y H:i') ?? '-' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
