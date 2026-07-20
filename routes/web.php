<?php

use App\Livewire\Auth\ForcePasswordChange;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');

Route::middleware(['auth'])->group(function () {
    Route::livewire('force-password-change', ForcePasswordChange::class)->name('force-password-change');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/users.php';
