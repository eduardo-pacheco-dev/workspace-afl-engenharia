<?php

use App\Livewire\Projects\ProjectForm;
use App\Livewire\Projects\ProjectList;
use App\Livewire\Projects\ProjectShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('projects', ProjectList::class)->name('projects.index');
    Route::livewire('projects/create', ProjectForm::class)->name('projects.create');
    Route::livewire('projects/{id}', ProjectShow::class)->name('projects.show');
    Route::livewire('projects/{id}/edit', ProjectForm::class)->name('projects.edit');
});
