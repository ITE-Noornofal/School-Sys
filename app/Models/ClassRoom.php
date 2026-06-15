<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassRoom extends Model
{
    protected $fillable = ['name', 'section','grade_id'];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class); // لاحقًا
    }

     public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function supervisor()
{
    return $this->belongsTo(Supervisor::class);
}

 public function bus()
    {
        return $this->belongsTo(Bus::class);
    }

    public function targetGroup()
    {
        return $this->belongsTo(TargetGroup::class);
    }

public function duePaymentTemplates()
{
    return $this->hasMany(DuePaymentTemplate::class);
}

}





