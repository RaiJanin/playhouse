<?php

namespace App\Enums;

enum MimoReport :string
{
    case OUTLET_SALES = 'mR001';
    case CASHIER = 'mR002';
    case HOUR_SALES = 'mR003';
    case ITEM_SALES = 'mR004';

    public function label() :string
    {
        return match ($this) {
            self::OUTLET_SALES => 'Outlet Sales Report',
            self::CASHIER => "Cashier's Report",
            self::HOUR_SALES => 'Sales Report by Hour',
            self::ITEM_SALES => 'Sales Report by Item',
        };
    }
}
