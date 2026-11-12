<?php

use App\Http\Controllers\PriceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('prices', [PriceController::class, 'index'])->name('prices.index');
Route::post('prices/convert', [PriceController::class, 'convert'])->name('prices.convert');
Route::post('prices/discount', [PriceController::class, 'discount'])->name('prices.discount');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
