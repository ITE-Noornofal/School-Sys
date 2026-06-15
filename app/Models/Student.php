<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class Student extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles;

    // حقل كلمة المرور ليس ضروري إذا لم تسجل كلمة مرور للطالب، لكن أتركته مع إمكانية التعديل لاحقًا
    protected $fillable = [
        'Full_name',
        'email',
        'password',
        'user_id',
        'class_room_id',
        'specialization_id',
        'age',
        'birth_date',
        'address',
        'guardian_name',
        'target_group_id',
         'guardian_id',  // تأكد أنه هنا
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'email_verified_at' => 'datetime',
    ];

    // العلاقات
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function specialization()
    {
        return $this->belongsTo(Specialization::class);
    }
     public function guardians()
    {
        return $this->belongsToMany(Guardian::class);
    }

      public function targetGroup()
    {
        return $this->belongsTo(TargetGroup::class);
    }



public function grade()
{
    return $this->classRoom->grade ?? null;
}
public function guardian()
{
    return $this->belongsTo(Guardian::class);
}

public function schoolTrips()
{
    return $this->belongsToMany(SchoolTrip::class)
        ->withPivot('confirmation_status')
        ->withTimestamps();
}

public function duePayments()
{
    return $this->hasMany(DuePayment::class);
}

public function classTransfers()
{
    return $this->hasMany(ClassTransfer::class);
}



}
