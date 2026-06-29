<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GetSellerDashboardController extends Controller
{
    private const BUSINESS_TIMEZONE = 'Asia/Jakarta';

    private const STORAGE_TIMEZONE = 'UTC';

    public function profile(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;
        $mainTenant = Tenant::query()
            ->where('owner_user_id', $sellerId)
            ->latest()
            ->first();

        return response()->json([
            'message' => 'Profil dashboard seller berhasil diambil.',
            'data' => [
                'seller' => [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                ],
                'store' => [
                    'id' => $mainTenant?->id,
                    'name' => $mainTenant?->name,
                    'profile_picture_url' => $mainTenant?->profile_picture_url,
                    'is_verified' => (bool) $mainTenant,
                    'verification_label' => $mainTenant ? 'Terverifikasi' : 'Belum terverifikasi',
                    'membership_label' => 'Tumbuh Member',
                ],
            ],
        ]);
    }

    public function todayRevenue(Request $request): JsonResponse
    {
        $todayRevenue = $this->revenueForDate($request->user()->id, CarbonImmutable::now(self::BUSINESS_TIMEZONE));

        return response()->json([
            'message' => 'Pendapatan seller hari ini berhasil diambil.',
            'data' => [
                'today_revenue' => $todayRevenue,
                'today_revenue_label' => $this->moneyLabel($todayRevenue),
            ],
        ]);
    }

    public function revenueChange(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;
        $today = CarbonImmutable::now(self::BUSINESS_TIMEZONE);
        $todayRevenue = $this->revenueForDate($sellerId, $today);
        $yesterdayRevenue = $this->revenueForDate($sellerId, $today->subDay());

        return response()->json([
            'message' => 'Perubahan pendapatan seller berhasil diambil.',
            'data' => [
                'today_revenue' => $todayRevenue,
                'today_revenue_label' => $this->moneyLabel($todayRevenue),
                'yesterday_revenue' => $yesterdayRevenue,
                'yesterday_revenue_label' => $this->moneyLabel($yesterdayRevenue),
                'change_percentage' => $this->percentageChange($todayRevenue, $yesterdayRevenue),
                'change_label' => $this->changeLabel($todayRevenue, $yesterdayRevenue),
            ],
        ]);
    }

    public function todayTransactions(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;
        $today = CarbonImmutable::now(self::BUSINESS_TIMEZONE);
        $todayTransactionCount = $this->transactionCountForDate($sellerId, $today);
        $yesterdayTransactionCount = $this->transactionCountForDate($sellerId, $today->subDay());

        return response()->json([
            'message' => 'Transaksi seller hari ini berhasil diambil.',
            'data' => [
                'today_transaction_count' => $todayTransactionCount,
                'yesterday_transaction_count' => $yesterdayTransactionCount,
                'change_percentage' => $this->percentageChange($todayTransactionCount, $yesterdayTransactionCount),
                'change_label' => $this->changeLabel($todayTransactionCount, $yesterdayTransactionCount),
            ],
        ]);
    }

    public function todayOrderCounts(Request $request): JsonResponse
    {
        $today = CarbonImmutable::now(self::BUSINESS_TIMEZONE);

        return response()->json([
            'message' => 'Count tab pesanan seller hari ini berhasil diambil.',
            'data' => $this->orderStatusCounts($this->ordersToday($request->user()->id, $today)),
            'meta' => $this->todayPeriodMeta($today),
        ]);
    }

    public function newOrderPreview(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;
        $today = CarbonImmutable::now(self::BUSINESS_TIMEZONE);

        return response()->json([
            'message' => 'Preview pesanan baru seller berhasil diambil.',
            'data' => $this->newOrderPreviewForDate($sellerId, $today),
        ]);
    }

    public function topProductsToday(Request $request): JsonResponse
    {
        return response()->json([
            'message' => 'Produk terlaris seller hari ini berhasil diambil.',
            'data' => $this->topProductsForDate($request->user()->id, CarbonImmutable::now(self::BUSINESS_TIMEZONE)),
        ]);
    }

    private function ordersToday(string $sellerId, ?CarbonImmutable $date = null): Collection
    {
        $date ??= CarbonImmutable::now(self::BUSINESS_TIMEZONE);
        [$start, $end] = $this->businessDateBounds($date);

        return Transaction::query()
            ->with(['items.tenant', 'user'])
            ->whereBetween('transaction_at', [$start, $end])
            ->whereHas('items.tenant', fn (Builder $query) => $query->where('owner_user_id', $sellerId))
            ->orderByDesc('transaction_at')
            ->orderByDesc('id')
            ->get();
    }

    public function __invoke(Request $request): JsonResponse
    {
        $sellerId = $request->user()->id;
        $today = CarbonImmutable::now(self::BUSINESS_TIMEZONE);
        $yesterday = $today->subDay();
        $profile = $this->profile($request)->getData(true)['data'];
        $todayRevenue = $this->revenueForDate($sellerId, $today);
        $yesterdayRevenue = $this->revenueForDate($sellerId, $yesterday);
        $todayTransactionCount = $this->transactionCountForDate($sellerId, $today);
        $yesterdayTransactionCount = $this->transactionCountForDate($sellerId, $yesterday);
        $ordersToday = $this->ordersToday($sellerId, $today);

        return response()->json([
            'message' => 'Dashboard seller berhasil diambil.',
            'data' => [
                'seller' => $profile['seller'],
                'store' => $profile['store'],
                'summary' => [
                    'today_revenue' => $todayRevenue,
                    'today_revenue_label' => $this->moneyLabel($todayRevenue),
                    'today_revenue_change_percentage' => $this->percentageChange($todayRevenue, $yesterdayRevenue),
                    'today_revenue_change_label' => $this->changeLabel($todayRevenue, $yesterdayRevenue),
                    'today_transaction_count' => $todayTransactionCount,
                    'today_transaction_change_percentage' => $this->percentageChange($todayTransactionCount, $yesterdayTransactionCount),
                    'today_transaction_change_label' => $this->changeLabel($todayTransactionCount, $yesterdayTransactionCount),
                ],
                'orders_today' => [
                    'status_counts' => $this->orderStatusCounts($ordersToday),
                    'preview' => $this->newOrderPreviewFromOrders($ordersToday, $sellerId),
                ],
                'top_products_today' => $this->topProductsForDate($sellerId, $today),
            ],
        ]);
    }

    private function revenueForDate(string $sellerId, CarbonImmutable $date): int
    {
        [$start, $end] = $this->businessDateBounds($date);

        return (int) TransactionItem::query()
            ->whereHas('tenant', fn (Builder $query) => $query->where('owner_user_id', $sellerId))
            ->whereHas('transaction', fn (Builder $query) => $query
                ->where('status', Transaction::STATUS_COMPLETED)
                ->whereBetween('transaction_at', [$start, $end]))
            ->sum('line_total');
    }

    private function transactionCountForDate(string $sellerId, CarbonImmutable $date): int
    {
        [$start, $end] = $this->businessDateBounds($date);

        return Transaction::query()
            ->whereBetween('transaction_at', [$start, $end])
            ->where('status', '!=', Transaction::STATUS_CANCELED)
            ->whereHas('items.tenant', fn (Builder $query) => $query->where('owner_user_id', $sellerId))
            ->distinct('transactions.id')
            ->count('transactions.id');
    }

    private function businessDateBounds(CarbonImmutable $date): array
    {
        return [
            $date->startOfDay()->setTimezone(self::STORAGE_TIMEZONE),
            $date->endOfDay()->setTimezone(self::STORAGE_TIMEZONE),
        ];
    }

    private function newOrderPreviewForDate(string $sellerId, CarbonImmutable $date): Collection
    {
        return $this->newOrderPreviewFromOrders($this->ordersToday($sellerId, $date), $sellerId);
    }

    private function newOrderPreviewFromOrders(Collection $orders, string $sellerId): Collection
    {
        return $orders
            ->filter(fn (Transaction $order): bool => $order->statusCode() === Transaction::STATUS_CODE_ACCEPTED_BY_STORE)
            ->take(3)
            ->map(fn (Transaction $order): array => $this->mapOrderPreview($order, $sellerId))
            ->values();
    }

    private function orderStatusCounts(Collection $orders): array
    {
        return [
            'new' => [
                'status_code' => Transaction::STATUS_CODE_ACCEPTED_BY_STORE,
                'label' => 'Baru',
                'count' => $orders->where('status', Transaction::STATUS_ACCEPTED_BY_STORE)->count(),
            ],
            'processing' => [
                'status_code' => Transaction::STATUS_CODE_PROCESSING,
                'label' => 'Diproses',
                'count' => $orders->where('status', Transaction::STATUS_PROCESSING)->count(),
            ],
            'on_the_way' => [
                'status_code' => Transaction::STATUS_CODE_ON_THE_WAY,
                'label' => 'Dikirim',
                'count' => $orders->where('status', Transaction::STATUS_ON_THE_WAY)->count(),
            ],
            'ready_for_pickup' => [
                'status_code' => Transaction::STATUS_CODE_READY_FOR_PICKUP,
                'label' => 'Siap Diambil',
                'count' => $orders->where('status', Transaction::STATUS_READY_FOR_PICKUP)->count(),
            ],
            'completed' => [
                'status_code' => Transaction::STATUS_CODE_COMPLETED,
                'label' => 'Selesai',
                'count' => $orders->where('status', Transaction::STATUS_COMPLETED)->count(),
            ],
        ];
    }

    private function todayPeriodMeta(CarbonImmutable $today): array
    {
        $dateLabel = $today->locale('id')->translatedFormat('d F Y');

        return [
            'period' => 'today',
            'date' => $today->toDateString(),
            'date_label' => $dateLabel,
            'display_label' => 'Hari ini - '.$dateLabel,
            'timezone' => self::BUSINESS_TIMEZONE,
        ];
    }

    private function mapOrderPreview(Transaction $order, string $sellerId): array
    {
        $items = $this->sellerItems($order, $sellerId);
        $itemSummary = $items
            ->take(2)
            ->map(fn (TransactionItem $item): string => $item->product_name.' '.$item->quantity.'x')
            ->implode(', ');

        if ($items->count() > 2) {
            $itemSummary .= ', +'.($items->count() - 2).' produk';
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'buyer' => [
                'id' => $order->user?->id,
                'name' => $order->user?->name,
                'email' => $order->user?->email,
                'phone' => $order->user?->phone,
            ],
            'store_name' => $items->first()?->tenant?->name,
            'item_summary' => $itemSummary,
            'seller_subtotal_amount' => (int) $items->sum('line_total'),
            'seller_subtotal_amount_label' => $this->moneyLabel((int) $items->sum('line_total')),
            'payment_method' => $order->payment_method,
            'payment_method_code' => $order->payment_method_code,
            'payment_method_option_name' => $order->payment_method_option_name,
            'delivery_method' => $order->delivery_method,
            'delivery_method_code' => $order->delivery_method_code,
            'status' => $order->status,
            'status_code' => $order->statusCode(),
            'can_process' => $order->statusCode() === Transaction::STATUS_CODE_ACCEPTED_BY_STORE,
            'can_reject' => $order->statusCode() === Transaction::STATUS_CODE_ACCEPTED_BY_STORE,
            'process_deadline_minutes' => 30,
            'process_deadline_label' => 'Segera proses maks 30 menit',
            'transaction_at' => $order->transaction_at?->toIso8601String(),
            'transaction_at_label' => $order->transaction_at?->timezone(self::BUSINESS_TIMEZONE)->translatedFormat('d M Y, H:i').' WIB',
        ];
    }

    private function sellerItems(Transaction $order, string $sellerId): Collection
    {
        return $order->items
            ->filter(fn (TransactionItem $item): bool => $item->tenant?->owner_user_id === $sellerId)
            ->values();
    }

    private function topProductsForDate(string $sellerId, CarbonImmutable $date): Collection
    {
        [$start, $end] = $this->businessDateBounds($date);

        return TransactionItem::query()
            ->select([
                'transaction_items.product_id',
                'transaction_items.product_name',
                DB::raw('SUM(transaction_items.quantity) as sold_quantity'),
                DB::raw('SUM(transaction_items.line_total) as revenue'),
                DB::raw('MAX(products.image_url) as image_url'),
                DB::raw('MAX(products.image_path) as image_path'),
            ])
            ->leftJoin('products', 'products.id', '=', 'transaction_items.product_id')
            ->whereHas('tenant', fn (Builder $query) => $query->where('owner_user_id', $sellerId))
            ->whereHas('transaction', fn (Builder $query) => $query
                ->where('status', Transaction::STATUS_COMPLETED)
                ->whereBetween('transaction_at', [$start, $end]))
            ->groupBy('transaction_items.product_id', 'transaction_items.product_name')
            ->orderByDesc('sold_quantity')
            ->orderByDesc('revenue')
            ->limit(3)
            ->get()
            ->map(fn ($product, int $index): array => [
                'rank' => $index + 1,
                'product_id' => $product->product_id,
                'name' => $product->product_name,
                'image_url' => $product->image_path
                    ? Storage::disk(Product::imageDisk())->url($product->image_path)
                    : $product->image_url,
                'sold_quantity' => (int) $product->sold_quantity,
                'sold_quantity_label' => ((int) $product->sold_quantity).' terjual hari ini',
                'revenue' => (int) $product->revenue,
                'revenue_label' => $this->moneyLabel((int) $product->revenue),
            ]);
    }

    private function percentageChange(int $current, int $previous): int
    {
        if ($previous === 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    private function changeLabel(int $current, int $previous): string
    {
        $percentage = $this->percentageChange($current, $previous);

        if ($percentage === 0) {
            return '0% dari hari kemarin';
        }

        $prefix = $percentage > 0 ? '+' : '';

        return $prefix.$percentage.'% dari hari kemarin';
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp. '.number_format($amount, 0, ',', '.');
    }
}
