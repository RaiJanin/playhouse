<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    protected $table = 'orlne_pay';

    protected $fillable = [
        'ordlne_ph_id',
        'ord_code_ph',
        'payment_method',
        'amount',
        'cash_tendered',
        'change_amnt',
        'reference',
        'remarks',
        'charge_account_id',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cash_tendered' => 'decimal:2',
        'change_amnt' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItems::class, 'ordlne_ph_id');
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'ord_code_ph', 'ord_code_ph');
    }

    public function chargeAccount()
    {
        return $this->belongsTo(ChargeAccount::class);
    }
}
