<?php

use App\Http\Controllers\PenjualanPrintController;
use App\Http\Controllers\CaptchaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/kasir', \App\Livewire\KasirPos::class)->name('kasir');
    Route::get('/penjualan/{penjualan}/print', PenjualanPrintController::class)->name('penjualan.print');
});

Route::get('/login', function () {
    return redirect('/admin');
})->name('login');

// Captcha reload endpoint
Route::get('/admin/captcha-reload', [CaptchaController::class, 'reloadCaptcha'])->name('custom-captcha-reload');
