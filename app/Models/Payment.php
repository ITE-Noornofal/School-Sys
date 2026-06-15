<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Payment extends Model
{
    protected $fillable = [
        'guardian_id',
        'accountant_id',
        'amount',
        'payment_date',
        'method',
        'note',
    ];

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function accountant()
    {
        return $this->belongsTo(Accountant::class);
    }
}
