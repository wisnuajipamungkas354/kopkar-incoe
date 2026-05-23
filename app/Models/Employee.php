<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Employee extends Model
{
    protected $fillable = [
        'npk',
        'nama_lengkap',
        'jk',
        'tempat_lahir',
        'tanggal_lahir',
        'alamat',
        'no_telp',
        'pendidikan_terakhir',
        'seksi',
        'grade_category',
        'employment_status',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function koperasiMember(): HasOne
    {
        return $this->hasOne(KoperasiMember::class);
    }

    public function koperasiManagements(): HasMany
    {
        return $this->hasMany(KoperasiManagement::class);
    }

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }
}