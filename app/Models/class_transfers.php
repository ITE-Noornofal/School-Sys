<?php


// app/Models/ClassTransfer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Class_transfers extends Model
{
    protected $fillable = [
        'student_id', 'from_class_room_id', 'to_class_room_id', 'transfer_date', 'notes'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fromClassRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'from_class_room_id');
    }

    public function toClassRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'to_class_room_id');
    }
}
