<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PenarikanSaldo extends Model
{
    protected $table = 'penarikan_saldo';

    protected $guarded = ['id'];

    public function detailPenarikanSaldo()
    {
        return $this->hasMany(DetailPenarikanSaldo::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeByEmployee(Builder $query): void
    {
        $query->where('employee_id', auth('web')->user()->userable->id);
    }
}
