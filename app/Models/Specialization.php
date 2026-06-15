<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialization extends Model
{
    protected $fillable = ['name'];

    // لو تبي تربطها بالعكس مع الطلاب (اختياري)
    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
