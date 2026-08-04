<?php

namespace App\Enums;

enum MimoReport :string
{
    case OUTLET_SALES = 'mR001';
    case TRANSACTION = 'mR002';
    case HOUR_SALES = 'mR003';
    case ITEM_SALES = 'mR004';
    case CASHIER = 'mR005';

    public function label() :string
    {
        return match ($this) {
            self::OUTLET_SALES => 'Outlet Sales Report',
            self::TRANSACTION => "Transaction Report",
            self::HOUR_SALES => 'Sales Report by Hour',
            self::ITEM_SALES => 'Sales Report by Item',
            self::CASHIER => 'Cashier Report',
        };
    }
}
