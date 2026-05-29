<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TurnstileLogs extends Model
{
    protected $table = 'turnstile_logs';
    public $timestamps = false;

    protected $fillable = [
        'datetime',
        'context'
    ];

    protected $casts = [
        'datetime' => 'datetime'
    ];
}
