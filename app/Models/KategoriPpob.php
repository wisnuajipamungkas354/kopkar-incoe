<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriPpob extends Model
{
    protected $table = 'kategori_ppob';
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }
}
