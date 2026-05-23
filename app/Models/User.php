<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'userable_id',
        'userable_type',
        'username',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function userable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    public function getKoperasiMemberAttribute()
    {
        if ($this->userable instanceof Employee) {
            return $this->userable->koperasiMember;
        }

        return null;
    }

    public function isEmployee(): bool
    {
        return $this->userable instanceof Employee;
    }

    public function isKoperasiStaff(): bool
    {
        return $this->userable instanceof KoperasiStaff;
    }

    public function isMember(): bool
    {
        return $this->isEmployee()
            && $this->userable->koperasiMember()->exists();
    }

    public function isManagement(): bool
    {
        return $this->isEmployee()
            && $this->userable->koperasiManagements()->exists();
    }
}