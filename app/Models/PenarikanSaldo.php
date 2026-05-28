<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenarikanSaldo extends Model
{
    protected $table = 'penarikan_saldo';

    protected $guarded = ['id'];

    public function detailPenarikanSaldo()
    {
        return $this->hasMany(DetailPenarikanSaldo::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'diajukan_oleh');
    }
}
