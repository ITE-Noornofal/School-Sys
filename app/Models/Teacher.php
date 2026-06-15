<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;

class Teacher extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'teacher'; // مهم جداً

    protected $fillable = [
        'email', 'name', 'password', 'subject', 'address', 'phone', 'profile_image'
    ];

    protected $hidden = ['password', 'remember_token'];
}








