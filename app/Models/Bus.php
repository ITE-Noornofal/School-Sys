<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{

     protected $fillable = [
        'name',
         'plate_number',
    ];
    public function classRooms()
    {
        return $this->hasMany(ClassRoom::class);
    }

}
