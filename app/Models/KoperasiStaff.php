<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class KoperasiStaff extends Model
{
    protected $table = 'koperasi_staff';

    protected $fillable = [
        'npk',
        'nama',
        'jk',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_telp',
        'jabatan',
        'hire_date',
        'employment_status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'hire_date' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }
}