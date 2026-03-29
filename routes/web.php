<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\TransaksiKeuanganController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Models\TransaksiKeuangan;
use Carbon\Carbon;

Route::get('/dashboard', function () {
    $totalMasuk = TransaksiKeuangan::where('tipe', 'masuk')->sum('nominal');
    $totalKeluar = TransaksiKeuangan::where('tipe', 'keluar')->sum('nominal');
    
    $transaksiTahunIni = TransaksiKeuangan::whereYear('tanggal', date('Y'))->count();
    
    // Simulasikan waktu login (menggunakan waktu sekarang)
    $waktuLogin = now()->format('d M Y, H:i');

    return view('dashboard', compact('totalMasuk', 'totalKeluar', 'transaksiTahunIni', 'waktuLogin'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('kategori', KategoriController::class);
    Route::resource('transaksi', TransaksiKeuanganController::class);
});

require __DIR__.'/auth.php';
