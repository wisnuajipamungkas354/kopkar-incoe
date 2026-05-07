<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $fillable = [
        'username',
        'email',
        'password',

        // Data pribadi
        'nama_anggota',
        'gender',
        'tanggal_lahir',
        'ext_tempat_lahir',
        'ext_alamat',
        'ext_pendidikan_terakhir',
        'no_telp',

        // Data keanggotaan
        'join_astra',
        'join_date',
        'employement_status',
        'grade_category',
        'seksi',
        'status_user',
        'level_user',

        // Data rekening
        'nama_bank',
        'no_rekening',
        'pemilik_no_rekening',

        // Ahli waris
        'ext_nama_ahli_waris',
        'ext_hubungan_ahli_waris',
        'ext_hubungan_lainnya',

        // Status aplikasi
        'ext_is_approved',

        // Auth Laravel
        'email_verified_at',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
