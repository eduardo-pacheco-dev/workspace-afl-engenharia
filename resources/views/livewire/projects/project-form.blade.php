<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl p-6">
    <flux:heading size="xl">{{ $projectId ? __('Edit Project') : __('Create Project') }}</flux:heading>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <form wire:submit="save" class="p-6">
            <div class="w-full max-w-2xl space-y-6">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus />

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:select wire:model="type" :label="__('Type')">
                        <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        @foreach ($types as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="operator" :label="__('Operator')">
                        <flux:select.option value="">{{ __('None') }}</flux:select.option>
                        @foreach ($operators as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:select wire:model="status" :label="__('Status')">
                        @foreach ($statuses as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:input wire:model="address" :label="__('Address')" type="text" />
                </div>

                <flux:textarea wire:model="description" :label="__('Description')" rows="3" />

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="responsible" :label="__('Responsible')" type="text" />
                    <flux:input wire:model="phone" :label="__('Phone')" type="tel" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="latitude" :label="__('Latitude')" type="number" step="0.00000001" min="-90" max="90" />
                    <flux:input wire:model="longitude" :label="__('Longitude')" type="number" step="0.00000001" min="-180" max="180" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="start_date" :label="__('Start Date')" type="date" />
                    <flux:input wire:model="end_date" :label="__('End Date')" type="date" />
                </div>

                <flux:textarea wire:model="notes" :label="__('Notes')" rows="3" />

                <div class="flex items-center gap-4 pt-4">
                    <flux:button variant="primary" type="submit">
                        {{ $projectId ? __('Update') : __('Create') }}
                    </flux:button>
                    <flux:button variant="ghost" href="{{ route('projects.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</div>
