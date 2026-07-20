<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <flux:heading size="xl">{{ $stationId ? __('Edit Station') : __('Create Station') }}</flux:heading>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <form wire:submit="save" class="p-6">
            <div class="w-full max-w-2xl space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="site_id" :label="__('Site ID')" type="text" required autofocus />
                    <flux:input wire:model="end_id" :label="__('End ID')" type="text" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:select wire:model="operadora" :label="__('Operadora')">
                        <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        @foreach ($operadoras as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="description" :label="__('Description')" type="text" />
                </div>

                <flux:input wire:model="address" :label="__('Address')" type="text" required />

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="latitude" :label="__('Latitude')" type="number" step="0.00000001" min="-90" max="90" />
                    <flux:input wire:model="longitude" :label="__('Longitude')" type="number" step="0.00000001" min="-180" max="180" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:select wire:model="status" :label="__('Status')">
                        @foreach ($statuses as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="type" :label="__('Type')">
                        <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        @foreach ($types as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="responsible" :label="__('Responsible')" type="text" />
                    <flux:input wire:model="phone" :label="__('Phone')" type="tel" />
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <flux:button variant="primary" type="submit">
                        {{ $stationId ? __('Update') : __('Create') }}
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('stations.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</div>
