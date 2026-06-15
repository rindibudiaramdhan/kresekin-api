<?php

namespace App\Support\Dashboard;

use Carbon\CarbonImmutable;

class DashboardPeriod
{
    public function __construct(
        public readonly string $value,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly CarbonImmutable $previousStart,
        public readonly CarbonImmutable $previousEnd,
    ) {}

    public static function from(?string $value): self
    {
        $normalized = in_array($value, ['30_days', '90_days'], true) ? $value : '30_days';
        $days = $normalized === '90_days' ? 90 : 30;

        $end = CarbonImmutable::now('Asia/Jakarta')->endOfDay();
        $start = $end->subDays($days - 1)->startOfDay();
        $previousEnd = $start->subDay()->endOfDay();
        $previousStart = $previousEnd->subDays($days - 1)->startOfDay();

        return new self($normalized, $start, $end, $previousStart, $previousEnd);
    }

    /**
     * @return array<int, array{date: string, label: string, transaction_count: int, revenue: int}>
     */
    public function emptyTrendPoints(): array
    {
        $points = [];

        for ($date = $this->start; $date->lessThanOrEqualTo($this->end); $date = $date->addDay()) {
            $points[$date->toDateString()] = [
                'date' => $date->toDateString(),
                'label' => $date->format('d M'),
                'transaction_count' => 0,
                'revenue' => 0,
            ];
        }

        return $points;
    }
}
