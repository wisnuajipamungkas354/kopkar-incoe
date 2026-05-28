<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengaturanPpobEmployee extends Model
{
    protected $table = 'pengaturan_ppob_employee';
    protected $guarded = ['id'];

    public function tagihanPayrollEmployee()
    {
        return $this->morphOne(TagihanPayrollEmployee::class, 'tagihanable');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
