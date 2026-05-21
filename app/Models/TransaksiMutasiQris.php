<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiMutasiQris extends Model
{
    protected $table = 'transaksi_mutasi_qris';

    protected $guarded = ['id'];

    public function transaksiMutasi(): BelongsTo
    {
        return $this->belongsTo(TransaksiMutasi::class);
    }
}
