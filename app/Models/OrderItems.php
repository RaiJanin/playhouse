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

    public function payments()
    {
        return $this->hasMany(OrderPayment::class, 'ordlne_ph_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $orderItem) {
            $reserved = [];

            if (empty($orderItem->qr_child)) {
                $orderItem->qr_child = self::generateUniqueQrNumber($reserved);
                $reserved[] = $orderItem->qr_child;
            }

            if (empty($orderItem->qr_guardian)) {
                $orderItem->qr_guardian = self::generateUniqueQrNumber($reserved);
                $reserved[] = $orderItem->qr_guardian;
            }
        });
    }

    /**
     * Random, zero-padded 6-digit QR code, unique across every row's
     * qr_child AND qr_guardian — turnstile lookups match against both
     * columns (see TurnstileController::turnstileSrchPOST), so a code
     * reused across columns would make a physical wristband scan ambiguous.
     * $reserved excludes codes already handed out earlier in the same
     * boot(creating) call, since the sibling column isn't saved yet to be
     * caught by the DB uniqueness check below.
     */
    private static function generateUniqueQrNumber(array $reserved = []): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (
            in_array($code, $reserved, true)
            || self::where('qr_child', $code)->exists()
            || self::where('qr_guardian', $code)->exists()
        );

        return $code;
    }

}
