<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Legacy outlet masterfile (table `outlet`), owned by the wider MIMO system —
 * no migration here creates or alters it. `ord_code` on this row doubles as
 * the running "next Official Receipt number" counter for the outlet, consumed
 * and incremented whenever a new `orhdr` (OfficialReceipt) row is written.
 */
class Outlet extends Model
{
    protected $table = 'outlet';
    protected $primaryKey = 'out_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'ord_code',
    ];
}
