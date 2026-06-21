<?php

namespace App\Support\Dashboard;

use App\Models\Transaction;

class DashboardFormatter
{
    public function money(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    public function number(int|float $value): string
    {
        return number_format($value, 0, ',', '.');
    }

    public function initials(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        return collect(explode(' ', trim($name)))
            ->filter()
            ->take(2)
            ->map(fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }

    public function statusLabel(?string $statusCode): string
    {
        return match ($statusCode) {
            Transaction::STATUS_CODE_COMPLETED => 'Success',
            Transaction::STATUS_CODE_PENDING_PAYMENT,
            Transaction::STATUS_CODE_ACCEPTED_BY_STORE,
            Transaction::STATUS_CODE_PROCESSING,
            Transaction::STATUS_CODE_ON_THE_WAY,
            Transaction::STATUS_CODE_READY_FOR_PICKUP => 'Pending',
            Transaction::STATUS_CODE_CANCELED => 'Failed',
            default => 'Pending',
        };
    }

    public function growthPercentage(int|float $current, int|float $previous): float
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
