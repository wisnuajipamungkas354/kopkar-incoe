<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::livewire('admin', 'pages::admin.dashboard');
Route::livewire('login', 'pages::auth.login');
Route::livewire('register', 'pages::auth.register');
Route::livewire('success', 'pages::auth.success');
Route::livewire('admin/anggota', 'pages::admin.anggota.index');
Route::livewire('admin/ppob', 'pages::admin.ppob.index');
Route::livewire('anggota', 'pages::anggota.dashboard');
Route::livewire('anggota/simpanan-pokok', 'pages::anggota.simpanan.pokok.index');
Route::livewire('anggota/simpanan-wajib', 'pages::anggota.simpanan.wajib.index');
Route::livewire('anggota/simpanan-sukarela', 'pages::anggota.simpanan.sukarela.index');
Route::livewire('anggota/tarik-saldo', 'pages::anggota.simpanan.tarik-saldo.index');
Route::livewire('anggota/lazis', 'pages::anggota.pembayaran.lazis.index');
Route::livewire('anggota/pembiayaan-pinjaman', 'pages::anggota.simpanan.pembiayaan-pinjaman.index');
Route::livewire('anggota/pembiayaan-pinjaman/pengajuan', 'pages::anggota.simpanan.pembiayaan-pinjaman.pengajuan');

Route::get('/logout', function () {
    \Illuminate\Support\Facades\Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');
