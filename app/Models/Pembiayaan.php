<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pembiayaan extends Model
{
    protected $table = 'pembiayaan';
    protected $guarded = ['id'];

    protected $casts = [
        'rincian_barang' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
