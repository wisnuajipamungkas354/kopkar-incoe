<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PpobDetailTagihan extends Model
{
    protected $table = 'ppob_detail_tagihan';
    protected $guarded = ['id'];

    public function transaksiMutasi()
    {
        return $this->belongsTo(TransaksiMutasi::class, 'transaksi_mutasi_id');
    }
}
