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
