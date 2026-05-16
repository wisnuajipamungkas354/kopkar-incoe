<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanUtama extends Model
{
    protected $fillable = [
        'user_id',
        'nomor_pengajuan',
        'tanggal_pengajuan',
        'total_pembiayaan_syariah',
        'margin_terakumulasi',
        'total_estimasi_nilai',
        'status_approval',
        'approved_bendahara',
        'approved_bendahara_at',
        'approved_ketua',
        'approved_ketua_at',
    ];

    // Relasi ke User Lama (Golang)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relasi ke Item Checklist (Many-to-Many via Pivot Table)
    public function items(): HasMany
    {
        return $this->hasMany(PengajuanItemJenis::class, 'pengajuan_utama_id');
    }

    // Relasi ke Referensi Pihak Ketiga
    public function referensiPihakKetiga(): HasMany
    {
        return $this->hasMany(PembiayaanReferensiPihakKetiga::class, 'pengajuan_utama_id');
    }
}
