<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DuePaymentTemplate extends Model
{
     protected $fillable = ['title', 'description', 'amount', 'penalty_per_day'];
     public function classRoom()
{
    return $this->belongsTo(ClassRoom::class);
}


public function grade()
{
    return $this->belongsTo(Grade::class);
}

}
