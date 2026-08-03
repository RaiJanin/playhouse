<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\M06;
use App\Models\OrderItems;
use Carbon\Carbon;

class Orders extends Model
{
    protected $table = 'ordhdr';
    protected $fillable = [
        'ord_code_ph',
        'parent',
        'mkt_code',
        'd_code',
        'total_amnt',
        'xtra_chrg_amnt',
        'disc_amnt',
        'fb_pp_url',
        'visitdate',
        'paid_amnt',
        'is_paid',
        'paid_at',
    ];

    protected $casts = [
        'visitdate' => 'date',
        'paid_amnt' => 'decimal:2',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];

    public function parentPl()
    {
        return $this->belongsTo(M06::class, 'd_code', 'd_code');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItems::class, 'ord_code_ph', 'ord_code_ph');
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class, 'ord_code_ph', 'ord_code_ph');
    }
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            $order->ord_code_ph = self::generateMonthlyOrderNo();
        });
    }

    private static function generateMonthlyOrderNo()
    {
        $now = Carbon::now();
        $year = substr($now->year, -2);
        $monthLetter = self::getMonthLetter($now->month);

        $startOfMonth = $now->copy()->startOfMonth();

        $lastOrder = self::where('created_at', '>=', $startOfMonth)
            ->where('ord_code_ph', 'like', $year . $monthLetter . '%')
            ->lockForUpdate()
            ->orderByDesc('ord_code_ph')
            ->first();

        if (!$lastOrder) {
            $sequence = 1;
        } else {
            $lastNumber = (int) substr($lastOrder->ord_code_ph, 3);
            $sequence = $lastNumber + 1;
        }

        return $year . $monthLetter . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    private static function getMonthLetter($month)
    {
        $letters = [
            1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D',
            5 => 'E', 6 => 'F', 7 => 'G', 8 => 'H',
            9 => 'I', 10 => 'J', 11 => 'K', 12 => 'L'
        ];

        return $letters[$month] ?? 'Z';
    }
}
