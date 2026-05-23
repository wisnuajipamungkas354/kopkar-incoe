<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::livewire('admin', 'pages::admin.dashboard');
Route::livewire('login', 'pages::auth.login');
Route::livewire('register', 'pages::auth.register');
Route::livewire('success', 'pages::auth.success');
Route::livewire('verify-email', 'pages::auth.verify-email')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (\Illuminate\Http\Request $request, $id, $hash) {
    $user = \App\Models\User::with('userable')->findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403, 'Tautan verifikasi tidak valid atau kadaluarsa.');
    }
    if ($user->hasVerifiedEmail()) {
        return redirect('/login')->with('status', 'Email sudah diverifikasi.');
    }
    $user->markEmailAsVerified();
    return redirect('/success')->with('nama_anggota', $user->userable->nama_lengkap);
})->middleware(['signed'])->name('verification.verify');

Route::livewire('admin/transaksi', 'pages::admin.transaksi.index');
Route::livewire('admin/anggota', 'pages::admin.anggota.index');
Route::livewire('admin/anggota/create', 'pages::admin.anggota.create');
Route::livewire('admin/anggota/{id}/edit', 'pages::admin.anggota.edit');
Route::livewire('admin/anggota/{id}', 'pages::admin.anggota.show');
Route::livewire('admin/simpanan-sukarela', 'pages::admin.simpanan-sukarela.index');
Route::livewire('admin/ppob', 'pages::admin.ppob.index');
Route::livewire('admin/lazis', 'pages::admin.lazis.index');
Route::livewire('admin/persetujuan/registrasi-anggota', 'pages::admin.persetujuan.registrasi-anggota.index');
Route::livewire('admin/persetujuan/simpanan-sukarela', 'pages::admin.persetujuan.simpanan-sukarela.index');
Route::livewire('admin/persetujuan/penarikan-saldo', 'pages::admin.persetujuan.penarikan-saldo.index');

Route::livewire('anggota', 'pages::anggota.dashboard');
Route::livewire('anggota/profile', 'pages::anggota.profile');
Route::livewire('anggota/simpanan-pokok', 'pages::anggota.dompet.pokok.index');
Route::livewire('anggota/simpanan-wajib', 'pages::anggota.dompet.wajib.index');
Route::livewire('anggota/simpanan-sukarela', 'pages::anggota.dompet.sukarela.index');
Route::livewire('anggota/tarik-saldo', 'pages::anggota.dompet.tarik-saldo.index');
Route::livewire('anggota/lazis', 'pages::anggota.pembayaran.lazis.index');
Route::livewire('anggota/ppob', 'pages::anggota.pembayaran.ppob.index');
Route::livewire('anggota/pembiayaan-pinjaman', 'pages::anggota.dompet.pembiayaan-pinjaman.index');
Route::livewire('anggota/pembiayaan-pinjaman/pengajuan', 'pages::anggota.dompet.pembiayaan-pinjaman.pengajuan');


Route::get('/logout', function () {
    \Illuminate\Support\Facades\Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');
