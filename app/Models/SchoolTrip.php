<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SchoolTrip extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'trip_date',
        'location',
        'class_room_id',
        'bus_id',
         'supervisor_id',
    ];



    // ✅ العلاقة مع الفئة المستهدفة (الصف أو المرحلة)
    public function targetGroup()
    {
        return $this->belongsTo(TargetGroup::class);
    }

    // ✅ العلاقة مع الباص
    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
public function supervisor()
{
    return $this->belongsTo(Supervisor::class);
}



public function classRoom()
{
    return $this->belongsTo(ClassRoom::class);
}







    // علاقة الرحلة بالطلاب المشاركين
    public function students()
    {
        return $this->belongsToMany(Student::class, 'school_trip_student')
            ->withPivot('confirmation_status')
            ->withTimestamps();
    }


}
