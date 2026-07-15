<?php

namespace App\Enums;

/**
 * Placeholder promo codes — replace/extend these cases with real business codes.
 * Discount amounts are always resolved server-side from here, never trusted from the client.
 */
enum PromoCode: string
{
    case NONE = '';
    case WELCOME50 = 'WELCOME50';
    case LOYALTY100 = 'LOYALTY100';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'No Promo',
            self::WELCOME50 => 'Welcome Promo (₱50 off)',
            self::LOYALTY100 => 'Loyalty Promo (₱100 off)',
        };
    }

    public function discount(): float
    {
        return match ($this) {
            self::NONE => 0,
            self::WELCOME50 => 50,
            self::LOYALTY100 => 100,
        };
    }

    /**
     * @return array<int, array{code: string, label: string, discount: float}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => [
                'code' => $case->value,
                'label' => $case->label(),
                'discount' => $case->discount(),
            ],
            self::cases()
        );
    }
}
