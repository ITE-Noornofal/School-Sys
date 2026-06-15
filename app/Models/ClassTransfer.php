<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassTransfer extends Model
{
    protected $fillable = [
        'student_id',
        'from_class_room_id',
        'to_class_room_id',
        'transferred_by',
        'reason',
        'transfer_date',
    ];

    // علاقة مع الطالب
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // علاقة مع الشعبة القديمة
    public function fromClassRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'from_class_room_id');
    }

    // علاقة مع الشعبة الجديدة
    public function toClassRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'to_class_room_id');
    }

    // علاقة مع المستخدم الذي نفذ النقل (اختياري)
    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

}
