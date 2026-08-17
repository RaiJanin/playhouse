<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Carbon\Carbon;

class DurationPrices extends Model
{
    protected $table = 'duration_prices';

    protected $fillable = [
        'duration_hour',
        'label', 
        'price'
    ];

    public function orderlines()
    {
        return $this->hasMany(OrderItems::class, 'durations_id', 'id');
    }

    protected function currentPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::now()->isWeekend() ? $this->weekend_price : $this->price,
        );
    }

    protected function currentPriceLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => Carbon::now()->isWeekend() ? $this->weekend_label : $this->label,
        );
    }
}
