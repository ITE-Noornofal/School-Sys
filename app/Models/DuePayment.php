<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DuePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'guardian_id',
        'accountant_id',
        'amount',
        'status',
        'description',
        'due_date',
        'student_id',
    ];

    // 🔁 علاقة مع ولي الأمر
    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    // 🔁 علاقة مع المحاسب
    public function accountant()
    {
        return $this->belongsTo(Accountant::class);
    }
}
