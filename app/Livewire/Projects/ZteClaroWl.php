<?php

namespace App\Livewire\Projects;

use App\Models\ServiceOrder;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app.sidebar')]
#[Title('ZTE Claro WL')]
class ZteClaroWl extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $priorityFilter = 'all';

    public bool $showFormModal = false;

    public bool $showDeleteModal = false;

    public ?int $editingId = null;

    public ?int $deletingId = null;

    public string $os_number = '';

    public string $title = '';

    public ?string $description = null;

    public string $status = 'open';

    public string $priority = 'medium';

    public ?string $responsible = null;

    public ?string $address = null;

    public ?string $scheduled_date = null;

    public ?string $completed_date = null;

    public ?string $notes = null;

    public function render()
    {
        return view('livewire.projects.zte-claro-wl', [
            'serviceOrders' => ServiceOrder::query()
                ->when($this->search !== '', fn ($query) => $query
                    ->where(fn ($q) => $q
                        ->where('os_number', 'like', "%{$this->search}%")
                        ->orWhere('title', 'like', "%{$this->search}%")
                        ->orWhere('responsible', 'like', "%{$this->search}%")
                        ->orWhere('address', 'like', "%{$this->search}%")
                    )
                )
                ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
                ->when($this->priorityFilter !== 'all', fn ($query) => $query->where('priority', $this->priorityFilter))
                ->orderByDesc('created_at')
                ->paginate(10),
            'statuses' => ServiceOrder::statuses(),
            'priorities' => ServiceOrder::priorities(),
        ]);
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->os_number = ServiceOrder::generateOsNumber();
        $this->showFormModal = true;
    }

    public function openEdit(int $id): void
    {
        $os = ServiceOrder::findOrFail($id);
        $this->editingId = $os->id;
        $this->os_number = $os->os_number;
        $this->title = $os->title;
        $this->description = $os->description;
        $this->status = $os->status;
        $this->priority = $os->priority;
        $this->responsible = $os->responsible;
        $this->address = $os->address;
        $this->scheduled_date = $os->scheduled_date?->format('Y-m-d');
        $this->completed_date = $os->completed_date?->format('Y-m-d');
        $this->notes = $os->notes;
        $this->showFormModal = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'os_number' => ['required', 'string', 'max:255', 'unique:service_orders,os_number,'.$this->editingId],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:open,in_progress,completed,cancelled'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'responsible' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'scheduled_date' => ['nullable', 'date'],
            'completed_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($this->editingId) {
            ServiceOrder::findOrFail($this->editingId)->update($validated);
            Flux::toast(variant: 'success', text: __('Service order updated successfully.'));
        } else {
            ServiceOrder::create($validated);
            Flux::toast(variant: 'success', text: __('Service order created successfully.'));
        }

        $this->closeForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $os = ServiceOrder::find($this->deletingId);
        if (! $os) {
            return;
        }
        $os->delete();
        $this->showDeleteModal = false;
        $this->deletingId = null;
        Flux::toast(variant: 'success', text: __('Service order deleted successfully.'));
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function closeForm(): void
    {
        $this->showFormModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->os_number = '';
        $this->title = '';
        $this->description = null;
        $this->status = 'open';
        $this->priority = 'medium';
        $this->responsible = null;
        $this->address = null;
        $this->scheduled_date = null;
        $this->completed_date = null;
        $this->notes = null;
    }
}
