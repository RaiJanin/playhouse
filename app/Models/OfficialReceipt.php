<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Legacy Official Receipt header (table `orhdr` — NOT `ordhdr`, which is this
 * app's own booking header, see App\Models\Orders). `orhdr` is a large table
 * shared across the wider MIMO system (car-service modules included); no
 * migration here creates or alters it. This app only ever inserts a row here
 * once a Playhouse booking becomes fully paid, linked back via `ord_code_ph`.
 */
class OfficialReceipt extends Model
{
    protected $table = 'orhdr';
    protected $primaryKey = 'ord_code';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'out_code',
        'ord_code',
        'customer',
        'ord_date',
        'net_amnt',
        'tax_amnt',
        'total_amnt',
        'disc_amnt',
        'user_id',
        't_date',
        't_time',
        'loc',
        'user_id2',
        't_date2',
        't_time2',
        'trnx_date',
        'debt_code',
        'branch',
        'reference',
        'payment',
        'pay_code',
        'pending',
        'amnt_tendered',
        'ord_amnt',
        'amnt_due',
        'due_date',
        'ord_code_ph',
        'payment2',
        'pay_code2',
        'amnt_tendered2',
    ];
}
