<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class GetFinanceSellerTransactionSubmissionListController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['paid', 'requested', 'approved', 'rejected'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 5);
        $items = $this->dummySubmissions()
            ->when($validated['search'] ?? null, function (Collection $items, string $search): Collection {
                $needle = mb_strtolower($search);

                return $items->filter(fn (array $item): bool => str_contains(mb_strtolower($item['id']), $needle)
                    || str_contains(mb_strtolower($item['store']['name']), $needle));
            })
            ->when($validated['status'] ?? null, fn (Collection $items, string $status): Collection => $items->where('status', $status))
            ->when($validated['date_from'] ?? null, fn (Collection $items, string $date): Collection => $items->where('requested_at', '>=', $date))
            ->when($validated['date_to'] ?? null, fn (Collection $items, string $date): Collection => $items->where('requested_at', '<=', $date))
            ->values();

        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return response()->json([
            'message' => 'Daftar transaksi pengajuan seller berhasil diambil.',
            'data' => $paginator->getCollection()->values(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    private function dummySubmissions(): Collection
    {
        $stores = [
            ['name' => 'Budi Sentosa Sembako Jaya', 'bank' => 'BCA', 'account' => '8830129xxx', 'amount' => 5200456, 'date' => '2026-01-02', 'status' => 'paid'],
            ['name' => 'Santi Organik Mart', 'bank' => 'Mandiri', 'account' => '1240098xxx', 'amount' => 2450999, 'date' => '2026-02-15', 'status' => 'requested'],
            ['name' => 'Asep Fashion Hijab', 'bank' => 'BNI', 'account' => '098122xxx', 'amount' => 875056, 'date' => '2026-03-06', 'status' => 'approved'],
            ['name' => 'Denny Retail Galon', 'bank' => 'BSI', 'account' => '012322xxx', 'amount' => 1025873, 'date' => '2026-03-06', 'status' => 'rejected'],
            ['name' => 'Rina Fresh Market', 'bank' => 'BRI', 'account' => '5521098xxx', 'amount' => 3199000, 'date' => '2026-04-12', 'status' => 'paid'],
            ['name' => 'Maya Frozen Food', 'bank' => 'BCA', 'account' => '7712098xxx', 'amount' => 1840500, 'date' => '2026-04-21', 'status' => 'requested'],
            ['name' => 'Toko Lestari Herbal', 'bank' => 'Mandiri', 'account' => '6677001xxx', 'amount' => 980250, 'date' => '2026-05-03', 'status' => 'approved'],
            ['name' => 'Galih Buah Segar', 'bank' => 'BNI', 'account' => '4409812xxx', 'amount' => 4211000, 'date' => '2026-05-18', 'status' => 'paid'],
            ['name' => 'Nadia Stationery', 'bank' => 'BSI', 'account' => '0192873xxx', 'amount' => 765000, 'date' => '2026-06-02', 'status' => 'rejected'],
            ['name' => 'Joko Home Care', 'bank' => 'BRI', 'account' => '3388001xxx', 'amount' => 1512500, 'date' => '2026-06-14', 'status' => 'requested'],
            ['name' => 'Citra Baby Shop', 'bank' => 'BCA', 'account' => '9088712xxx', 'amount' => 2710000, 'date' => '2026-07-01', 'status' => 'approved'],
            ['name' => 'Wawan Dapur Kita', 'bank' => 'Mandiri', 'account' => '1200981xxx', 'amount' => 652000, 'date' => '2026-07-23', 'status' => 'paid'],
            ['name' => 'Putri Personal Care', 'bank' => 'BNI', 'account' => '7776012xxx', 'amount' => 3350250, 'date' => '2026-08-09', 'status' => 'requested'],
            ['name' => 'Hendra Meat Corner', 'bank' => 'BSI', 'account' => '2291008xxx', 'amount' => 5890000, 'date' => '2026-08-27', 'status' => 'approved'],
            ['name' => 'Yuni Toiletries', 'bank' => 'BRI', 'account' => '5566120xxx', 'amount' => 1422750, 'date' => '2026-09-04', 'status' => 'rejected'],
            ['name' => 'Farhan Minuman Dingin', 'bank' => 'BCA', 'account' => '3412788xxx', 'amount' => 2240500, 'date' => '2026-09-19', 'status' => 'paid'],
            ['name' => 'Alya Peralatan Rumah', 'bank' => 'Mandiri', 'account' => '6799012xxx', 'amount' => 3975000, 'date' => '2026-10-01', 'status' => 'requested'],
            ['name' => 'Bagas Mart', 'bank' => 'BNI', 'account' => '8883122xxx', 'amount' => 1899000, 'date' => '2026-10-08', 'status' => 'approved'],
            ['name' => 'Siska Snack Corner', 'bank' => 'BSI', 'account' => '7752311xxx', 'amount' => 1185000, 'date' => '2026-10-17', 'status' => 'paid'],
            ['name' => 'Rafi Sayur Online', 'bank' => 'BRI', 'account' => '9900123xxx', 'amount' => 2067500, 'date' => '2026-10-24', 'status' => 'requested'],
            ['name' => 'Mega Food Supply', 'bank' => 'BCA', 'account' => '1122871xxx', 'amount' => 4523000, 'date' => '2026-10-28', 'status' => 'approved'],
            ['name' => 'Andi Grosir Beras', 'bank' => 'Mandiri', 'account' => '8809211xxx', 'amount' => 7250000, 'date' => '2026-10-29', 'status' => 'paid'],
            ['name' => 'Tia Obat Sehat', 'bank' => 'BNI', 'account' => '6601298xxx', 'amount' => 1333000, 'date' => '2026-10-30', 'status' => 'rejected'],
            ['name' => 'Fajar Frozen Corner', 'bank' => 'BSI', 'account' => '4190002xxx', 'amount' => 2998750, 'date' => '2026-10-31', 'status' => 'requested'],
        ];

        return collect($stores)->map(function (array $store, int $index): array {
            return [
                'id' => 'WD-20230914-'.str_pad((string) ($index + 42), 4, '0', STR_PAD_LEFT),
                'store' => ['name' => $store['name']],
                'bank' => [
                    'name' => $store['bank'],
                    'account_number_masked' => $store['account'],
                ],
                'amount' => $store['amount'],
                'amount_label' => $this->moneyLabel($store['amount']),
                'requested_at' => $store['date'],
                'requested_at_label' => $this->dateLabel($store['date']),
                'status' => $store['status'],
                'status_label' => $this->statusLabel($store['status']),
            ];
        });
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Berhasil',
            'approved' => 'Diproses',
            'rejected' => 'Ditolak',
            default => 'Pengajuan',
        };
    }

    private function dateLabel(string $date): string
    {
        return CarbonImmutable::parse($date)->locale('id')->translatedFormat('j M Y');
    }

    private function moneyLabel(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }
}
