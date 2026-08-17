<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
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

    /**
     * العلاقة مع الصفوف الدراسية
     * GradeLevel يحتوي على supervisor_id
     */
    public function gradeLevels()
    {
        return $this->hasMany(GradeLevel::class, 'supervisor_id');
    }

    /**
     * إذا كنت تريد إبقاء اسم grades
     * يمكن استخدامه كـ alias للعلاقة
     */
    public function grades()
    {
        return $this->hasMany(GradeLevel::class, 'supervisor_id');
    }

    /**
     * العلاقة مع الشعب
     *
     * احذفها إذا كان class_rooms لا يحتوي supervisor_id
     */
    // public function classRooms()
    // {
    //     return $this->hasMany(ClassRoom::class, 'supervisor_id');
    // }
}
