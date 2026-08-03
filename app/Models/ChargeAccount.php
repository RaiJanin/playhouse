<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChargeAccount extends Model
{
    protected $table = 'charge_accounts';

    protected $fillable = [
        'name',
        'contact_person',
        'mobileno',
    ];

    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }
}
