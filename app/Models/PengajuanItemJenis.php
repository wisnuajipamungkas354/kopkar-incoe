<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanItemJenis extends Model
{
    protected $fillable = [
        'pengajuan_utama_id',
        'kategori_utama',
        'sub_jenis',
        'nominal_per_item',
        'tenor_bulan',
        'deskripsi'
    ];

    public function pengajuanUtama(): BelongsTo
    {
        return $this->belongsTo(PengajuanUtama::class, 'pengajuan_utama_id');
    }

    // Relasi kondisional ke rincian barang jika memilih sub_jenis 'barang'
    public function rincianBarang(): HasMany
    {
        return $this->hasMany(PembiayaanRincianBarang::class, 'pengajuan_item_jenis_id');
    }
}
