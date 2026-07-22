<?php

namespace App\Livewire\Freelancers;

use App\Models\Freelancer;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
#[Title('Freelancer Form')]
class FreelancerForm extends Component
{
    public ?int $freelancerId = null;

    public string $name = '';

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $cpf_cnpj = null;

    public ?string $specialization = null;

    public string $status = 'active';

    public ?float $hourly_rate = null;

    public ?string $address = null;

    public ?string $notes = null;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $freelancer = Freelancer::findOrFail($id);
            $this->freelancerId = $freelancer->id;
            $this->name = $freelancer->name;
            $this->email = $freelancer->email;
            $this->phone = $freelancer->phone;
            $this->cpf_cnpj = $freelancer->cpf_cnpj;
            $this->specialization = $freelancer->specialization;
            $this->status = $freelancer->status;
            $this->hourly_rate = $freelancer->hourly_rate;
            $this->address = $freelancer->address;
            $this->notes = $freelancer->notes;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'string', 'max:255', 'email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'cpf_cnpj' => ['nullable', 'string', 'max:18'],
            'specialization' => ['nullable', 'in:electrical,mechanical,civil,telecommunications,automation,it,other'],
            'status' => ['required', 'in:active,inactive'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($this->freelancerId) {
            $freelancer = Freelancer::findOrFail($this->freelancerId);
            $freelancer->update($validated);
            Flux::toast(variant: 'success', text: __('Freelancer updated successfully.'));
        } else {
            Freelancer::create($validated);
            Flux::toast(variant: 'success', text: __('Freelancer created successfully.'));
        }

        $this->redirectRoute('freelancers.index');
    }

    public function render()
    {
        return view('livewire.freelancers.freelancer-form', [
            'statuses' => Freelancer::statuses(),
            'specializations' => Freelancer::specializations(),
        ]);
    }
}
