<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{
    protected $table = 'ordlne_ph';
    protected $fillable = [
        'ord_code_ph',
        'd_code_child',
        'guardian',
        'durationhours',
        'durationsubtotal',
        'socksqty',
        'socksprice',
        'subtotal',
        'disc_code',
        'disc_amnt',
        'others_amnt',
        'cash_tendered',
        'change_amnt',
        'is_paid',
        'paid_at',
        'checked_out',
        'lne_xtra_chrg',
        'notified_timeout',
        'durations_id',
        'ckin',
        'ckout',
        'bkout',
        'bkin',
        'isfreeze',
        'qr_child',
        'qr_guardian'
    ];

    protected $casts = [
        'ckin' => 'datetime',
        'ckout' => 'datetime',
        'bkout' => 'datetime',
        'bkin' => 'datetime',
        'isfreeze' => 'boolean',
        'checked_out' => 'boolean',
        'durationsubtotal' => 'decimal:2',
        'socksprice' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'others_amnt' => 'decimal:2',
        'disc_amnt' => 'decimal:2',
        'lne_xtra_chrg' => 'decimal:2',
        'cash_tendered' => 'decimal:2',
        'change_amnt' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Orders::class, 'ord_code_ph', 'ord_code_ph');
    }

    public function child()
    {
        return $this->belongsTo(M06Child::class, 'd_code_child', 'd_code_c');
    }

    public function durationhoursprices()
    {
        return $this->belongsTo(DurationPrices::class, 'durations_id', 'id');
    }

}
