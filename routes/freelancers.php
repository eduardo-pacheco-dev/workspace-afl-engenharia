<?php

use App\Livewire\Freelancers\FreelancerForm;
use App\Livewire\Freelancers\FreelancerList;
use App\Livewire\Freelancers\FreelancerShow;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('freelancers', FreelancerList::class)->name('freelancers.index');
    Route::livewire('freelancers/create', FreelancerForm::class)->name('freelancers.create');
    Route::livewire('freelancers/{id}', FreelancerShow::class)->name('freelancers.show');
    Route::livewire('freelancers/{id}/edit', FreelancerForm::class)->name('freelancers.edit');
});
