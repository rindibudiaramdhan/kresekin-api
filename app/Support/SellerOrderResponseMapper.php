<?php

namespace App\Support;

use App\Models\Transaction;

class SellerOrderResponseMapper
{
    public function sharedFields(Transaction $order): array
    {
        return [
            'buyer' => $this->buyer($order),
            'delivery_method' => $order->delivery_method,
            'delivery_method_code' => $order->delivery_method_code,
            'payment_method' => $order->payment_method,
            'payment_method_code' => $order->payment_method_code,
            'payment_method_option_name' => $order->payment_method_option_name,
            'payment_method_option_code' => $order->payment_method_option_code,
        ];
    }

    private function buyer(Transaction $order): array
    {
        $usesSnapshot = $order->buyer_address_snapshot_at !== null;

        return [
            'id' => $order->user?->id,
            'name' => $order->user?->name,
            'email' => $order->user?->email,
            'phone' => $order->user?->phone,
            'address' => $usesSnapshot ? $order->buyer_address : $order->user?->address,
            'landmark' => $usesSnapshot ? $order->buyer_landmark : $order->user?->landmark,
            'latitude' => $usesSnapshot ? $order->buyer_latitude : $order->user?->latitude,
            'longitude' => $usesSnapshot ? $order->buyer_longitude : $order->user?->longitude,
        ];
    }
}
