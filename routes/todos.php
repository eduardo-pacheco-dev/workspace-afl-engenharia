<?php

use App\Livewire\Todos\TodoForm;
use App\Livewire\Todos\TodoList;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::livewire('todos', TodoList::class)->name('todos.index');
    Route::livewire('todos/create', TodoForm::class)->name('todos.create');
    Route::livewire('todos/{id}/edit', TodoForm::class)->name('todos.edit');
});
