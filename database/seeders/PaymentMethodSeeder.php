<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $paymentMethods = [
            [
                'code' => PaymentMethod::BANK_TRANSFER,
                'name' => 'Transfer Bank',
                'icon_key' => 'bank_transfer',
                'requires_option' => true,
                'sort_order' => 1,
                'is_active' => true,
                'options' => [
                    ['code' => 'bca', 'name' => 'BCA', 'icon_key' => 'bank_bca', 'sort_order' => 1],
                    ['code' => 'mandiri', 'name' => 'Mandiri', 'icon_key' => 'bank_mandiri', 'sort_order' => 2],
                    ['code' => 'bsi', 'name' => 'BSI', 'icon_key' => 'bank_bsi', 'sort_order' => 3],
                    ['code' => 'bni', 'name' => 'BNI', 'icon_key' => 'bank_bni', 'sort_order' => 4],
                ],
            ],
            // [
            //     'code' => PaymentMethod::QR_PAYMENT,
            //     'name' => 'QR Payment',
            //     'icon_key' => 'qris',
            //     'requires_option' => false,
            //     'sort_order' => 2,
            //     'is_active' => true,
            //     'options' => [],
            // ],
            // [
            //     'code' => PaymentMethod::COD,
            //     'name' => 'COD',
            //     'icon_key' => 'cod',
            //     'requires_option' => false,
            //     'sort_order' => 3,
            //     'is_active' => true,
            //     'options' => [],
            // ],
        ];

        foreach ($paymentMethods as $paymentMethodData) {
            $options = $paymentMethodData['options'];
            unset($paymentMethodData['options']);

            $paymentMethod = PaymentMethod::query()->updateOrCreate(
                ['code' => $paymentMethodData['code']],
                $paymentMethodData
            );

            foreach ($options as $option) {
                $paymentMethod->options()->updateOrCreate(
                    ['code' => $option['code']],
                    array_merge($option, ['is_active' => true])
                );
            }
        }
    }
}
