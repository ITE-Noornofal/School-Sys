<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'Full_name',
        'father_name',
        'mother_name',
        'phone_number',
        'gender',
        'birth_date',
        'address',
        'academic_year',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // لا نضع password => hashed هنا
            // لأننا نستخدم Hash::make() في Controller
        ];
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }
}
