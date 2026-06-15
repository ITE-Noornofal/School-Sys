<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable, HasRoles ,  HasFactory;

    protected $guard_name = 'admin';

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password'];
}



