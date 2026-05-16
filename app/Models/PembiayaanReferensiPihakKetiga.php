<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembiayaanReferensiPihakKetiga extends Model
{
    protected $fillable = ['pengajuan_utama_id', 'nama_lembaga', 'no_telp_wa', 'alamat'];

    public function pengajuanUtama(): BelongsTo
    {
        return $this->belongsTo(PengajuanUtama::class, 'pengajuan_utama_id');
    }
}
