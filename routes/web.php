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
