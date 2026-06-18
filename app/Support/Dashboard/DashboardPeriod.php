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
        $normalized = in_array($value, ['monthly', 'weekly', '30_days', '90_days'], true) ? $value : 'monthly';
        $now = CarbonImmutable::now('Asia/Jakarta');

        if ($normalized === 'monthly') {
            $start = $now->startOfMonth()->startOfDay();
            $end = $now->endOfMonth()->endOfDay();
            $previousStart = $start->subMonthNoOverflow()->startOfMonth()->startOfDay();
            $previousEnd = $previousStart->endOfMonth()->endOfDay();

            return new self($normalized, $start, $end, $previousStart, $previousEnd);
        }

        if ($normalized === 'weekly') {
            $start = $now->startOfWeek()->startOfDay();
            $end = $now->endOfWeek()->endOfDay();
            $previousStart = $start->subWeek()->startOfDay();
            $previousEnd = $previousStart->endOfWeek()->endOfDay();

            return new self($normalized, $start, $end, $previousStart, $previousEnd);
        }

        $days = $normalized === '90_days' ? 90 : 30;

        $end = $now->endOfDay();
        $start = $end->subDays($days - 1)->startOfDay();
        $previousEnd = $start->subDay()->endOfDay();
        $previousStart = $previousEnd->subDays($days - 1)->startOfDay();

        return new self($normalized, $start, $end, $previousStart, $previousEnd);
    }

    public function dateRangeLabel(): string
    {
        if ($this->start->isSameMonth($this->end)) {
            return sprintf('%s - %s', $this->start->format('M j'), $this->end->format('M j'));
        }

        return sprintf('%s - %s', $this->start->format('M j'), $this->end->format('M j'));
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
