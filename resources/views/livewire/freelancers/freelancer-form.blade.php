<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <flux:heading size="xl">{{ $freelancerId ? __('Edit Freelancer') : __('Create Freelancer') }}</flux:heading>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <form wire:submit="save" class="p-6">
            <div class="w-full max-w-2xl space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />
                    <flux:input wire:model="email" :label="__('Email')" type="email" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="phone" :label="__('Phone')" type="tel" />
                    <flux:input wire:model="cpf_cnpj" :label="__('CPF/CNPJ')" type="text" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:select wire:model="specialization" :label="__('Specialization')">
                        <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        @foreach ($specializations as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="status" :label="__('Status')">
                        @foreach ($statuses as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="hourly_rate" :label="__('Hourly Rate (R$)')" type="number" step="0.01" min="0" />
                    <flux:input wire:model="address" :label="__('Address')" type="text" />
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />

                <div class="flex items-center gap-4 pt-4">
                    <flux:button variant="primary" type="submit">
                        {{ $freelancerId ? __('Update') : __('Create') }}
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('freelancers.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</div>
