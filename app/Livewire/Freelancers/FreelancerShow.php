<?php

namespace App\Livewire\Freelancers;

use App\Models\Freelancer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
#[Title('Freelancer Details')]
class FreelancerShow extends Component
{
    public ?Freelancer $freelancer = null;

    public function mount(int $id): void
    {
        $this->freelancer = Freelancer::findOrFail($id);
    }

    public function render()
    {
        return view('livewire.freelancers.freelancer-show', [
            'statuses' => Freelancer::statuses(),
            'specializations' => Freelancer::specializations(),
        ]);
    }
}
