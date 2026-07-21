<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'))->name('home');

Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/todos.php';
require __DIR__.'/users.php';
require __DIR__.'/stations.php';
require __DIR__.'/freelancers.php';
