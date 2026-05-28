<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MutasiSaldoMember extends Model
{
    protected $table = 'mutasi_saldo_member';

    protected $guarded = ['id'];

    public function scopeByEmployee(Builder $query)
    {
        return $query->where('employee_id', auth('web')->user()->userable->id);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}