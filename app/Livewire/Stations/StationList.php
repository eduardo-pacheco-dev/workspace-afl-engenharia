<?php

namespace App\Livewire\Stations;

use App\Models\Station;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app.sidebar')]
#[Title('Stations')]
class StationList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public bool $showDeleteModal = false;
    public ?int $stationToDeleteId = null;

    public function render()
    {
        return view('livewire.stations.station-list', [
            'stations' => Station::query()
                ->where('site_id', 'like', "%{$this->search}%")
                ->orWhere('end_id', 'like', "%{$this->search}%")
                ->orWhere('address', 'like', "%{$this->search}%")
                ->orWhere('responsible', 'like', "%{$this->search}%")
                ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
                ->orderByDesc('created_at')
                ->paginate(10),
        ]);
    }

    public function confirmDelete(int $id): void
    {
        $this->stationToDeleteId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $station = Station::find($this->stationToDeleteId);
        if (! $station) {
            return;
        }
        $station->delete();
        $this->showDeleteModal = false;
        $this->stationToDeleteId = null;
        Flux::toast(variant: 'success', text: __('Station deleted successfully.'));
    }

    public function cancelDelete(): void
    {
        $this->showDeleteModal = false;
        $this->stationToDeleteId = null;
    }
}
