<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    protected $fillable = [
    'guardian_id',
    'guardian_id_enc',
    'guardian_id_hash',
    'student_id',
    'student_id_enc',
    'student_id_hash',
    'class_room_id',
    'content',
    'is_anonymous',
    'status',
];


    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }


}
