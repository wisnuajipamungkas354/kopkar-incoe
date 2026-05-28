<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RekeningKoperasi extends Model
{
    protected $table = 'rekening_koperasi';

    protected $guarded = ['id'];

    public function mutasiKasKoperasi()
    {
        return $this->hasMany(MutasiKasKoperasi::class);
    }
}
