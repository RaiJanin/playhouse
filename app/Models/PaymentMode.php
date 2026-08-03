<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Legacy "Mode of Payment" masterfile (m10), owned by the wider MIMO system —
 * this app only reads it, no migration here creates or alters it.
 */
class PaymentMode extends Model
{
    /** The m10 code that means "physical cash" — the only mode needing tendered/change math. */
    const CASH_CODE = 'CSH';

    /** App-only pseudo-mode, not an m10 row — bills a charge_accounts record instead of collecting money. */
    const CHARGE_CODE = 'charge';

    protected $table = 'm10';
    protected $primaryKey = 'mp_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $casts = [
        'active' => 'boolean',
        'iscredit' => 'boolean',
    ];
}
