<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TransaksiMutasi extends Model
{
    protected $table = 'transaksi_mutasi';

    protected $guarded = ['id'];

    protected static function booted()
    {
        static::creating(function ($transaksi) {
            // 1. Mapping Kategori Transaksi ke Kode Singkatan Prefix
            $prefixList = [
                'pokok'              => 'TX-PK',  // Pokok
                'wajib'              => 'TX-WJ',  // Wajib
                'sukarela'           => 'TX-SS',  // Sukarela
                'smp_lain_lain'      => 'TX-SLL',  // Lain-lain
                'shu'                => 'TX-SHU', // Sisa Hasil Usaha
                'ppob'               => 'TX-PPOB', // Payment Point Online Bank (Pulsa, PLN, dll)
                'lazis'              => 'TX-LZS', // Lembaga Amil Zakat, Infaq, Shadaqah
                'pembiayaan'         => 'TX-PBY', // Pembiayaan (Sisi Syariah/Koperasi)
                'pinjaman'           => 'TX-PJM', // Pinjaman
            ];

            // Ambil prefix berdasarkan kategori, jika tidak terdaftar gunakan default 'TX-GEN'
            $prefix = $prefixList[$transaksi->kategori_transaksi] ?? 'TX-GEN';

            // 2. Ambil tanggal hari ini (Format: YYYYMMDD, Contoh: 20260518)
            $hariIni = Carbon::now()->format('Ymd');

            // 3. Cari nomor transaksi terakhir pada hari ini dengan PREFIX yang sama
            // Hal ini agar nomor urut per kategori tidak saling tabrakan
            $transaksiTerakhir = self::where('nomor_transaksi', 'LIKE', $prefix . '-' . $hariIni . '-%')
                ->latest('id')
                ->first();

            // 4. Tentukan Nomor Urut Baru
            if ($transaksiTerakhir) {
                // Mengambil 4 digit angka paling belakang
                $nomorUrutTerakhir = substr($transaksiTerakhir->nomor_transaksi, -4);
                $nomorUrutBaru = intval($nomorUrutTerakhir) + 1;
            } else {
                $nomorUrutBaru = 1;
            }

            $nomorUrutFormat = str_pad($nomorUrutBaru, 4, '0', STR_PAD_LEFT);

            // 5. Gabungkan Menjadi Nomor Transaksi Otomatis
            // Contoh Hasil: TX-SK-20260518-0001
            $transaksi->nomor_transaksi = $prefix . "-" . $hariIni . "-" . $nomorUrutFormat;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaksiMutasiQris(): HasOne
    {
        return $this->hasOne(TransaksiMutasiQris::class);
    }

    public function ppobDetailTagihan(): HasOne
    {
        return $this->hasOne(PpobDetailTagihan::class, 'transaksi_mutasi_id');
    }
}
