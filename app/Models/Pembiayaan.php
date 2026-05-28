<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Pembiayaan extends Model
{
    protected $table = 'pembiayaan';
    protected $guarded = ['id'];

    protected $casts = [
        'rincian_barang' => 'array',
    ];

    public function scopeByEmployee(Builder $query): void {
        $query->where('employee_id', auth('web')->user()->userable->id);
    }

    public function tagihanPayrollEmployee()
    {
        return $this->morphMany(TagihanPayrollEmployee::class, 'tagihanable');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
