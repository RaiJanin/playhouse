<?php

namespace App\Enums;

/**
 * Placeholder promo codes — replace/extend these cases with real business codes.
 * Discount amounts are always resolved server-side from here, never trusted from the client.
 */
enum PromoCode: string
{
    case NONE = '';
    case PWDDISC = 'PWD DISCOUNT';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'No Promo',
            self::PWDDISC => 'PWD DISCOUNT',
        };
    }

    public function discount(): float
    {
        return match ($this) {
            self::NONE => 0,
            self::PWDDISC => 76,
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
