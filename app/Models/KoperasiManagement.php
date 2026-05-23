<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class KoperasiManagement extends Model
{
    protected $table = 'koperasi_management';

    protected $fillable = [
        'employee_id',
        'jabatan',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'userable');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}