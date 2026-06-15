<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{

       protected $fillable = ['name'];

    public function classRooms(): HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function supervisors()
    {
        return $this->belongsToMany(Supervisor::class, 'grade_supervisor');
    }

public function supervisor()
{
    return $this->belongsTo(Supervisor::class);
}


public function duePaymentTemplates()
{
    return $this->hasMany(DuePaymentTemplate::class);
}





}
