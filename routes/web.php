<?php

use App\Http\Controllers\PenjualanPrintController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/kasir', \App\Livewire\KasirPos::class)->name('kasir');
    Route::get('/penjualan/{penjualan}/print', PenjualanPrintController::class)->name('penjualan.print');
});

Route::get('/login', function () {
    return redirect('/admin');
})->name('login');
