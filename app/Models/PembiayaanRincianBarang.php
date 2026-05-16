<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PembiayaanRincianBarang extends Model
{
    protected $fillable = ['pengajuan_item_jenis_id', 'nama_barang_jasa', 'harga'];

    public function itemJenis(): BelongsTo
    {
        return $this->belongsTo(PengajuanItemJenis::class, 'pengajuan_item_jenis_id');
    }
}
