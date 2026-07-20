<?php

use App\Livewire\Users\UserForm;
use App\Livewire\Users\UserList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('users', UserList::class)->name('users.index');
    Route::livewire('users/create', UserForm::class)->name('users.create');
    Route::livewire('users/{id}/edit', UserForm::class)->name('users.edit');
});
