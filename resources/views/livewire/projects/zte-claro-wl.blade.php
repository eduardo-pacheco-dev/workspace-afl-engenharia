<div class="flex h-full w-full flex-1 flex-col rounded-xl p-8">
    {{-- Form Modal --}}
    <flux:modal name="service-order-form" wire:model="showFormModal" class="max-w-2xl">
        <div class="space-y-6">
            <flux:heading size="lg">{{ $editingId ? __('Edit Service Order') : __('New Service Order') }}</flux:heading>

            <form wire:submit="save" class="space-y-6">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="os_number" :label="__('Nº OS')" type="text" required />
                    <flux:input wire:model="title" :label="__('Título')" type="text" required />
                </div>

                <flux:textarea wire:model="description" :label="__('Descrição')" rows="3" />

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:select wire:model="status" :label="__('Status')">
                        @foreach ($statuses as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="priority" :label="__('Prioridade')">
                        @foreach ($priorities as $value => $label)
                            <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="responsible" :label="__('Responsável')" type="text" />
                    <flux:input wire:model="address" :label="__('Endereço')" type="text" />
                </div>

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <flux:input wire:model="scheduled_date" :label="__('Data de Agendamento')" type="date" />
                    <flux:input wire:model="completed_date" :label="__('Data de Conclusão')" type="date" />
                </div>

                <flux:textarea wire:model="notes" :label="__('Observações')" rows="3" />

                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="closeForm">{{ __('Cancel') }}</flux:button>
                    <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Delete Modal --}}
    <flux:modal name="confirm-os-deletion" wire:model="showDeleteModal" focusable class="max-w-lg">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">{{ __('Are you sure you want to delete this service order?') }}</flux:heading>
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

    <div class="mb-6 flex items-center justify-between">
        <flux:heading size="xl">ZTE Claro WL - Ordens de Serviço</flux:heading>
        <div class="flex items-center gap-2">
            <flux:button variant="primary" wire:click="openCreate">
                {{ __('Nova OS') }}
            </flux:button>
            <flux:button variant="ghost" href="{{ route('projects.index') }}" wire:navigate>
                {{ __('Back to Projects') }}
            </flux:button>
        </div>
    </div>

    <div class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        <div class="border-b border-neutral-200 p-5 dark:border-neutral-700">
            <div class="flex flex-col gap-4 sm:flex-row">
                <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by OS number, title, responsible or address...') }}" icon="magnifying-glass" class="flex-1" />
                <flux:select wire:model.live="statusFilter" class="w-full sm:w-48">
                    <flux:select.option value="all">{{ __('All Status') }}</flux:select.option>
                    @foreach ($statuses as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="priorityFilter" class="w-full sm:w-48">
                    <flux:select.option value="all">{{ __('All Priorities') }}</flux:select.option>
                    @foreach ($priorities as $value => $label)
                        <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-neutral-200 dark:divide-neutral-700">
                <thead class="bg-neutral-50 dark:bg-neutral-800">
                    <tr>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">{{ __('Nº OS') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">{{ __('Título') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">{{ __('Prioridade') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">{{ __('Responsável') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">{{ __('Agendamento') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">{{ __('Status') }}</th>
                        <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-neutral-500 dark:text-neutral-400">{{ __('Ações') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 bg-white dark:divide-neutral-700 dark:bg-neutral-900">
                    @forelse ($serviceOrders as $os)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-800">
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <span class="font-medium">{{ $os->os_number }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                {{ $os->title }}
                                @if ($os->description)
                                    <p class="mt-1 text-sm text-neutral-500 dark:text-neutral-400">{{ Str::limit($os->description, 60) }}</p>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                @if ($os->priority === 'urgent')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">{{ $priorities[$os->priority] }}</span>
                                @elseif ($os->priority === 'high')
                                    <span class="inline-flex items-center rounded-full bg-orange-100 px-2.5 py-0.5 text-xs font-medium text-orange-800 dark:bg-orange-900 dark:text-orange-300">{{ $priorities[$os->priority] }}</span>
                                @elseif ($os->priority === 'medium')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">{{ $priorities[$os->priority] }}</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">{{ $priorities[$os->priority] }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">{{ $os->responsible ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                {{ $os->scheduled_date?->format('d/m/Y') ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                @if ($os->status === 'open')
                                    <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300">{{ $statuses[$os->status] }}</span>
                                @elseif ($os->status === 'in_progress')
                                    <span class="inline-flex items-center rounded-full bg-yellow-100 px-2.5 py-0.5 text-xs font-medium text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">{{ $statuses[$os->status] }}</span>
                                @elseif ($os->status === 'completed')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300">{{ $statuses[$os->status] }}</span>
                                @elseif ($os->status === 'cancelled')
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300">{{ $statuses[$os->status] }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="openEdit({{ $os->id }})" class="inline-flex items-center rounded-md p-1.5 text-neutral-500 hover:bg-neutral-100 hover:text-neutral-700 dark:text-neutral-400 dark:hover:bg-neutral-700 dark:hover:text-neutral-200" aria-label="{{ __('Edit') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $os->id }})" class="inline-flex items-center rounded-md p-1.5 text-neutral-500 hover:bg-red-100 hover:text-red-600 dark:text-neutral-400 dark:hover:bg-red-900 dark:hover:text-red-400" aria-label="{{ __('Delete') }}">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-neutral-500 dark:text-neutral-400">
                                {{ __('No service orders found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-neutral-200 px-6 py-4 dark:border-neutral-700">
            {{ $serviceOrders->links() }}
        </div>
    </div>
</div>
