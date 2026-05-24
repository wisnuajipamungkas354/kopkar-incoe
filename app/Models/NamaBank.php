<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NamaBank extends Model
{
    protected $table = 'nama_bank';

    protected $fillable = [
        'kode_bank',
        'nama_bank',
    ];
}
