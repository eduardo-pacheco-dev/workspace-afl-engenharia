<?php

use App\Livewire\Stations\StationForm;
use App\Livewire\Stations\StationList;
use App\Livewire\Stations\StationShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('stations', StationList::class)->name('stations.index');
    Route::livewire('stations/create', StationForm::class)->name('stations.create');
    Route::livewire('stations/{id}', StationShow::class)->name('stations.show');
    Route::livewire('stations/{id}/edit', StationForm::class)->name('stations.edit');
});
