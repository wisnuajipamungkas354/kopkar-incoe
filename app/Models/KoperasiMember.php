<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class KoperasiMember extends Model
{
    protected $fillable = [
        'employee_id',
        'member_number',

        'join_koperasi_astra',
        'join_date',
        'leave_date',

        'status',
        'is_approved',
        'approved_at',

        'nama_ahli_waris',
        'hubungan_ahli_waris',
        'hubungan_lainnya',

        'saldo_simpanan_pokok',
        'saldo_simpanan_wajib',
        'saldo_simpanan_sukarela',
        'saldo_simpanan_lain_lain',
        'saldo_shu',
    ];

    protected function casts(): array
    {
        return [
            'join_koperasi_astra' => 'date',
            'join_date' => 'date',
            'leave_date' => 'date',
            'approved_at' => 'datetime',

            'is_approved' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}