<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\LaporanController;

Route::get('/laporan/pdf', [LaporanController::class, 'generatePDF'])->name('laporan.pdf');

Route::get('/', function () {
    return view('welcome');
});
