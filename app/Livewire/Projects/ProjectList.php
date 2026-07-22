<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app.sidebar')]
#[Title('Projects')]
class ProjectList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $typeFilter = 'all';

    public bool $showDeleteModal = false;

    public ?int $projectToDeleteId = null;

    public function render()
    {
        return view('livewire.projects.project-list', [
            'projects' => Project::query()
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('operator', 'like', "%{$this->search}%")
                ->orWhere('address', 'like', "%{$this->search}%")
                ->orWhere('responsible', 'like', "%{$this->search}%")
                ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
                ->when($this->typeFilter !== 'all', fn ($query) => $query->where('type', $this->typeFilter))
                ->orderByDesc('created_at')
                ->paginate(10),
            'statuses' => Project::statuses(),
            'types' => Project::types(),
            'operators' => Project::operators(),
        ]);
    }

    public function confirmDelete(int $id): void
    {
        $this->projectToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $project = Project::find($this->projectToDeleteId);
        if (! $project) {
            return;
        }
        $project->delete();
        $this->showDeleteModal = false;
        $this->projectToDeleteId = null;
        Flux::toast(variant: 'success', text: __('Project deleted successfully.'));
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->projectToDeleteId = null;
    }
}
