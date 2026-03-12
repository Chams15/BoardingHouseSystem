<?php

use App\Http\Controllers\RoomController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        if (auth()->user()->role === 'Admin') {
            return redirect()->route('admin.dashboard');
        }

        return inertia('dashboard');
    })->name('dashboard');

    Route::get('rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('rooms/{room}/request', [RoomController::class, 'requestRoom'])->name('rooms.request');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
