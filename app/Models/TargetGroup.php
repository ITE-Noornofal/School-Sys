<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TargetGroup extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function trips()
    {
        return $this->hasMany(SchoolTrip::class);
    }
     public function classRooms()
    {
        return $this->hasMany(ClassRoom::class);
    }

}
