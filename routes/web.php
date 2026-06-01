<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::livewire('login', 'pages::auth.login')->name('login');
Route::livewire('register', 'pages::auth.register')->name('register');
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

Route::group(['middleware' => ['auth', 'verified']], function () {
    // Rute untuk admin
    Route::group(['prefix' => 'admin', 'middleware' => ['role:admin']], function () {
        Route::livewire('', 'pages::admin.dashboard');
        Route::livewire('profile', 'pages::admin.profile');        
        Route::livewire('transaksi', 'pages::admin.transaksi.index');
        Route::livewire('anggota', 'pages::admin.anggota.index');
        Route::livewire('anggota/create', 'pages::admin.anggota.create');
        Route::livewire('anggota/{id}/edit', 'pages::admin.anggota.edit');
        Route::livewire('anggota/{id}', 'pages::admin.anggota.show');
        Route::livewire('employee', 'pages::admin.master-data.employee.index');
        Route::livewire('employee/create', 'pages::admin.master-data.employee.create');
        Route::livewire('employee/{id}/edit', 'pages::admin.master-data.employee.edit');
        Route::livewire('koperasi-staff', 'pages::admin.master-data.koperasi-staff.index');
        Route::livewire('koperasi-staff/create', 'pages::admin.master-data.koperasi-staff.create');
        Route::livewire('koperasi-staff/{id}/edit', 'pages::admin.master-data.koperasi-staff.edit');
        Route::livewire('koperasi-management', 'pages::admin.koperasi-management.index');
        Route::livewire('nama-bank', 'pages::admin.nama-bank.index');
        Route::livewire('kategori-ppob', 'pages::admin.kategori-ppob.index');
        Route::livewire('simpanan-sukarela', 'pages::admin.simpanan-sukarela.index');
        Route::livewire('ppob', 'pages::admin.ppob.index');
        Route::livewire('lazis', 'pages::admin.lazis.index');
        Route::livewire('persetujuan/registrasi-anggota', 'pages::admin.persetujuan.registrasi-anggota.index');
        Route::livewire('persetujuan/simpanan-sukarela', 'pages::admin.persetujuan.simpanan-sukarela.index');
        Route::livewire('persetujuan/penarikan-saldo', 'pages::admin.persetujuan.penarikan-saldo.index');
        Route::livewire('persetujuan/lazis', 'pages::admin.persetujuan.lazis.index');
        Route::livewire('persetujuan/pinjaman', 'pages::admin.persetujuan.pinjaman.index');
        Route::livewire('persetujuan/pembiayaan', 'pages::admin.persetujuan.pembiayaan.index');
        Route::livewire('pinjaman', 'pages::admin.pinjaman.index');
        Route::livewire('pinjaman/create', 'pages::admin.pinjaman.create');
        Route::livewire('pinjaman/{id}/edit', 'pages::admin.pinjaman.edit');
        Route::livewire('pembiayaan', 'pages::admin.pembiayaan.index');
        Route::livewire('pembiayaan/create', 'pages::admin.pembiayaan.create');
        Route::livewire('pembiayaan/{id}/edit', 'pages::admin.pembiayaan.edit');
        Route::livewire('mutasi-kas', 'pages::admin.mutasi-kas.index');
        Route::livewire('potongan-payroll', 'pages::admin.potongan-payroll.index');
    });
    // Rute untuk anggota
    Route::group(['prefix' => 'anggota', 'middleware' => ['role:anggota']], function () {
        Route::livewire('', 'pages::anggota.dashboard');
        Route::livewire('profile', 'pages::anggota.profile');
        Route::livewire('simpanan-pokok', 'pages::anggota.dompet.pokok.index');
        Route::livewire('simpanan-wajib', 'pages::anggota.dompet.wajib.index');
        Route::livewire('dompet', 'pages::anggota.dompet.index');
        Route::redirect('simpanan-sukarela', 'anggota/dompet');
        Route::redirect('tarik-saldo', 'anggota/dompet');
        Route::livewire('pembayaran', 'pages::anggota.pembayaran.index');
        Route::redirect('lazis', 'anggota/pembayaran');
        Route::redirect('ppob', 'anggota/pembayaran');
        Route::livewire('pembiayaan-pinjaman', 'pages::anggota.pembiayaan-pinjaman.index');
        Route::livewire('pembiayaan-pinjaman/pengajuan', 'pages::anggota.pembiayaan-pinjaman.pengajuan');
    });
});



Route::get('/logout', function () {
    \Illuminate\Support\Facades\Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/login');
})->name('logout');
