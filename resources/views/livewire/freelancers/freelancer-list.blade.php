<div class="flex h-full w-full flex-1 flex-col rounded-xl p-8">
    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">{{ __('Freelancers') }}</flux:heading>
        <flux:button variant="primary" href="{{ route('freelancers.create') }}" wire:navigate>
            {{ __('Add Freelancer') }}
        </flux:button>
    </div>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="border-b border-neutral-200 p-5 dark:border-neutral-700">
            <div class="flex flex-col gap-4 sm:flex-row">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by name, email, CPF/CNPJ or specialization...') }}" icon="magnifying-glass" class="flex-1" />
                <flux:select wire:model.live="statusFilter" class="w-full sm:w-48">
                    <flux:select.option value="all">{{ __('All') }}</flux:select.option>
                    <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                    <flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option>
                </flux:select>
            </div>
        </div>

        <flux:table>
            <flux:table.columns>
                <flux:table.column class="pr-8 pl-12 py-4 text-center">{{ __('Name') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Email') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('CPF/CNPJ') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Specialization') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Hourly Rate') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Status') }}</flux:table.column>
                <flux:table.column class="px-8 py-4 text-center">{{ __('Actions') }}</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($freelancers as $freelancer)
                    <flux:table.row>
                        <flux:table.cell class="px-8 py-5">
                            <span class="font-medium">{{ $freelancer->name }}</span>
                            @if ($freelancer->phone)
                                <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ $freelancer->phone }}</p>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">{{ $freelancer->email ?? '-' }}</flux:table.cell>
                        <flux:table.cell class="px-8 py-5">{{ $freelancer->cpf_cnpj ?? '-' }}</flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            @if ($freelancer->specialization)
                                <flux:badge size="sm">{{ $specializations[$freelancer->specialization] ?? $freelancer->specialization }}</flux:badge>
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            @if ($freelancer->hourly_rate)
                                R$ {{ number_format($freelancer->hourly_rate, 2, ',', '.') }}
                            @else
                                <span class="text-neutral-400">-</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            @if ($freelancer->status === 'active')
                                <flux:badge color="green" size="sm">{{ __('Ativo') }}</flux:badge>
                            @elseif ($freelancer->status === 'inactive')
                                <flux:badge color="red" size="sm">{{ __('Inativo') }}</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell class="px-8 py-5">
                            <div class="flex items-center gap-2">
                                <flux:button icon="pencil-square" variant="ghost" size="sm" href="{{ route('freelancers.edit', $freelancer->id) }}" wire:navigate aria-label="{{ __('Edit') }}" />
                                <flux:button icon="trash" variant="ghost" size="sm" wire:click="confirmDelete({{ $freelancer->id }})" aria-label="{{ __('Delete') }}" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @empty
                        <flux:table.row>
                            <flux:table.cell colspan="7" class="px-6 py-12 text-center">
                                <flux:text>{{ __('No freelancers found.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
            </flux:table.rows>
        </flux:table>

        <div class="border-t border-neutral-200 px-6 py-4 dark:border-neutral-700">
            {{ $freelancers->links() }}
        </div>
    </div>

    <flux:modal name="confirm-freelancer-deletion" wire:model="showDeleteModal" focusable class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Are you sure you want to delete this freelancer?') }}</flux:heading>
                <flux:subheading>{{ __('This action cannot be undone.') }}</flux:subheading>
            </div>
            <div class="flex justify-end space-x-2 rtl:space-x-reverse">
                <flux:modal.close>
                    <flux:button variant="filled" wire:click="cancelDelete">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button variant="danger" wire:click="delete">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
