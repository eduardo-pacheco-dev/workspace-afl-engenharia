<?php

namespace App\Livewire\Freelancers;

use App\Models\Freelancer;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app.sidebar')]
#[Title('Freelancers')]
class FreelancerList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public bool $showDeleteModal = false;
    public ?int $freelancerToDeleteId = null;

    public function render()
    {
        return view('livewire.freelancers.freelancer-list', [
            'freelancers' => Freelancer::query()
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('cpf_cnpj', 'like', "%{$this->search}%")
                ->orWhere('specialization', 'like', "%{$this->search}%")
                ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
                ->orderByDesc('created_at')
                ->paginate(10),
        ]);
    }

    public function confirmDelete(int $id): void
    {
        $this->freelancerToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $freelancer = Freelancer::find($this->freelancerToDeleteId);
        if (! $freelancer) {
            return;
        }
        $freelancer->delete();
        $this->showDeleteModal = false;
        $this->freelancerToDeleteId = null;
        Flux::toast(variant: 'success', text: __('Freelancer deleted successfully.'));
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->freelancerToDeleteId = null;
    }
}
