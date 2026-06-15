<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable; // لأن ولي الأمر يسجل دخول
use Laravel\Sanctum\HasApiTokens; // لو تستخدم Sanctum لتوثيق API
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles; // لو تستخدم صلاحيات Spatie




class Guardian extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // علاقة ولي الأمر مع الطلاب
    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function payments()
{
    return $this->hasMany(Payment::class);
}

public function duePayments()
{
    return $this->hasMany(DuePayment::class);
}
}
