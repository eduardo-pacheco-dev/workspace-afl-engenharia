<?php

namespace App\Livewire\Stations;

use App\Models\Station;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app.sidebar')]
#[Title('Station Form')]
class StationForm extends Component
{
    public ?int $stationId = null;

    public string $site_id = '';

    public ?string $end_id = null;

    public ?string $operadora = null;

    public string $description = '';

    public string $address = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public string $status = 'active';

    public ?string $type = null;

    public ?string $responsible = null;

    public ?string $phone = null;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $station = Station::findOrFail($id);
            $this->stationId = $station->id;
            $this->site_id = $station->site_id;
            $this->end_id = $station->end_id;
            $this->operadora = $station->operadora;
            $this->description = $station->description ?? '';
            $this->address = $station->address;
            $this->latitude = $station->latitude;
            $this->longitude = $station->longitude;
            $this->status = $station->status;
            $this->type = $station->type;
            $this->responsible = $station->responsible;
            $this->phone = $station->phone;
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'site_id' => ['required', 'string', 'max:255'],
            'end_id' => ['nullable', 'string', 'max:255'],
            'operadora' => ['nullable', 'in:TIM,VIVO,CLARO,Outra'],
            'description' => ['nullable', 'string', 'max:1000'],
            'address' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'status' => ['required', 'in:active,inactive,maintenance'],
            'type' => ['nullable', 'in:substation,distribution,transmission,generation'],
            'responsible' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        if ($this->stationId) {
            $station = Station::findOrFail($this->stationId);
            $station->update($validated);
            Flux::toast(variant: 'success', text: __('Station updated successfully.'));
        } else {
            Station::create($validated);
            Flux::toast(variant: 'success', text: __('Station created successfully.'));
        }

        $this->redirectRoute('stations.index');
    }

    public function render()
    {
        return view('livewire.stations.station-form', [
            'statuses' => Station::statuses(),
            'types' => Station::types(),
            'operadoras' => Station::operadoras(),
        ]);
    }
}
