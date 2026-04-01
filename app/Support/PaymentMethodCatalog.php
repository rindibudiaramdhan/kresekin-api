<?php

namespace App\Support;

class PaymentMethodCatalog
{
    public const BANK_TRANSFER = 'bank_transfer';
    public const QR_PAYMENT = 'qr_payment';
    public const COD = 'cod';

    public static function all(): array
    {
        return [
            self::BANK_TRANSFER => [
                'id' => 1,
                'code' => self::BANK_TRANSFER,
                'name' => 'Transfer Bank',
                'icon_key' => 'bank_transfer',
                'requires_option' => true,
                'options' => [
                    [
                        'id' => 101,
                        'code' => 'bca',
                        'name' => 'BCA',
                        'icon_key' => 'bank_bca',
                    ],
                    [
                        'id' => 102,
                        'code' => 'mandiri',
                        'name' => 'Mandiri',
                        'icon_key' => 'bank_mandiri',
                    ],
                    [
                        'id' => 103,
                        'code' => 'bsi',
                        'name' => 'BSI',
                        'icon_key' => 'bank_bsi',
                    ],
                    [
                        'id' => 104,
                        'code' => 'bni',
                        'name' => 'BNI',
                        'icon_key' => 'bank_bni',
                    ],
                ],
            ],
            self::QR_PAYMENT => [
                'id' => 2,
                'code' => self::QR_PAYMENT,
                'name' => 'QR Payment',
                'icon_key' => 'qris',
                'requires_option' => false,
                'options' => [],
            ],
            self::COD => [
                'id' => 3,
                'code' => self::COD,
                'name' => 'COD',
                'icon_key' => 'cod',
                'requires_option' => false,
                'options' => [],
            ],
        ];
    }

    public static function codes(): array
    {
        return array_keys(self::all());
    }

    public static function find(?string $code): ?array
    {
        if (! $code) {
            return null;
        }

        return self::all()[$code] ?? null;
    }
}
