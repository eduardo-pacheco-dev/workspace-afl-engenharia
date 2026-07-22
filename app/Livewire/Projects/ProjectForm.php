<?php

namespace App\Livewire\Projects;

use App\Models\Project;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
#[Title('Project Form')]
class ProjectForm extends Component
{
    public ?int $projectId = null;

    public string $name = '';

    public ?string $type = null;

    public string $status = 'active';

    public ?string $description = null;

    public ?string $operator = null;

    public ?string $address = null;

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?string $responsible = null;

    public ?string $phone = null;

    public ?string $start_date = null;

    public ?string $end_date = null;

    public ?string $notes = null;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $project = Project::findOrFail($id);
            $this->projectId = $project->id;
            $this->name = $project->name;
            $this->type = $project->type;
            $this->status = $project->status;
            $this->description = $project->description;
            $this->operator = $project->operator;
            $this->address = $project->address;
            $this->latitude = $project->latitude;
            $this->longitude = $project->longitude;
            $this->responsible = $project->responsible;
            $this->phone = $project->phone;
            $this->start_date = $project->start_date?->format('Y-m-d');
            $this->end_date = $project->end_date?->format('Y-m-d');
            $this->notes = $project->notes;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'in:zte_claro_wl,zte_claro,zte_vivo,huawei_claro,huawei_vivo,nokia_tim,other'],
            'status' => ['required', 'in:active,inactive,completed,suspended'],
            'description' => ['nullable', 'string', 'max:5000'],
            'operator' => ['nullable', 'in:CLARO,VIVO,TIM,OTHER'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'responsible' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($this->projectId) {
            $project = Project::findOrFail($this->projectId);
            $project->update($validated);
            Flux::toast(variant: 'success', text: __('Project updated successfully.'));
        } else {
            Project::create($validated);
            Flux::toast(variant: 'success', text: __('Project created successfully.'));
        }

        $this->redirectRoute('projects.index');
    }

    public function render()
    {
        return view('livewire.projects.project-form', [
            'statuses' => Project::statuses(),
            'types' => Project::types(),
            'operators' => Project::operators(),
        ]);
    }
}
