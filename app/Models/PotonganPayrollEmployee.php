<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PotonganPayrollEmployee extends Model
{
    protected $table = 'potongan_payroll_employee';
    protected $guarded = ['id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
