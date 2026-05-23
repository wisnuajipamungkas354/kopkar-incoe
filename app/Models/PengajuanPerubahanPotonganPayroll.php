<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanPerubahanPotonganPayroll extends Model
{
    protected $table = 'pengajuan_perubahan_potongan_payroll';
    protected $guarded = ['id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
