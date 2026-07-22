<?php

use App\Livewire\Projects\ProjectForm;
use App\Livewire\Projects\ProjectList;
use App\Livewire\Projects\ProjectShow;
use App\Livewire\Projects\ZteClaroWl;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('projects', ProjectList::class)->name('projects.index');
    Route::livewire('projects/create', ProjectForm::class)->name('projects.create');
    Route::livewire('projects/{id}', ProjectShow::class)->name('projects.show')->whereNumber('id');
    Route::livewire('projects/{id}/edit', ProjectForm::class)->name('projects.edit')->whereNumber('id');

    Route::livewire('projects/zte-claro-wl', ZteClaroWl::class)->name('projects.zte-claro-wl');
});
