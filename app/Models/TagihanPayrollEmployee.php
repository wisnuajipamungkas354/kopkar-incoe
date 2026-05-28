<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagihanPayrollEmployee extends Model
{
    protected $table = 'tagihan_payroll_employee';
    protected $guarded = ['id'];

    public function tagihanable()
    {
        return $this->morphTo();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
