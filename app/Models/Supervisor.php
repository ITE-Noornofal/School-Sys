<?php

namespace App\Models;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable; // لو المشرف يسجل دخول (Auth)
use Laravel\Sanctum\HasApiTokens; // لو تستخدم Sanctum للتوكن
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Supervisor extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;
    protected $guard_name = 'supervisor';
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // العلاقة مع الصفوف (Grades)
    // public function grades()
    // {
    //     return $this->belongsToMany(Grade::class, 'grade_supervisor');
    // }


    public function classRooms()
{
    return $this->hasMany(ClassRoom::class);
}

public function grades()
{
    return $this->hasMany(Grade::class);
}


}
