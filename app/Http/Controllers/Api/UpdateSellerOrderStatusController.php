<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSellerOrderStatusRequest;
use App\Models\CancellationReasonCategory;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UpdateSellerOrderStatusController extends Controller
{
    public function __invoke(UpdateSellerOrderStatusRequest $request, string $id): JsonResponse
    {
        $sellerId = $request->user()->id;

        $order = Transaction::query()
            ->with('statusHistories')
            ->where('id', $id)
            ->whereHas('items.tenant', fn ($query) => $query->where('owner_user_id', $sellerId))
            ->first();

        if (! $order) {
            return response()->json([
                'message' => 'Order tidak ditemukan.',
            ], Response::HTTP_NOT_FOUND);
        }

        if (in_array($order->statusCode(), [
            Transaction::STATUS_CODE_COMPLETED,
            Transaction::STATUS_CODE_CANCELED,
        ], true)) {
            throw ValidationException::withMessages([
                'status_code' => ['Status order yang sudah selesai atau dibatalkan tidak dapat diubah.'],
            ]);
        }

        $validated = $request->validated();
        $status = Transaction::statusFromCode($validated['status_code']);

        $this->ensureAllowedTransition($order, $validated['status_code']);

        $cancellationReasonCategory = $status === Transaction::STATUS_CANCELED
            ? CancellationReasonCategory::query()->find($validated['cancellation_reason_category_id'])
            : null;

        DB::transaction(function () use ($order, $status, $validated, $cancellationReasonCategory): void {
            $order->forceFill([
                'status' => $status,
                'cancellation_reason_category_id' => $status === Transaction::STATUS_CANCELED ? $cancellationReasonCategory?->id : null,
                'cancellation_reason_text' => $status === Transaction::STATUS_CANCELED ? ($validated['cancellation_reason_text'] ?? null) : null,
            ])->save();

            TransactionStatusHistory::query()->create([
                'transaction_id' => $order->id,
                'status' => $status,
                'title' => $this->statusTitle($status),
                'description' => $validated['description'] ?? $this->statusDescription($status, $cancellationReasonCategory, $validated['cancellation_reason_text'] ?? null),
                'sequence' => ((int) $order->statusHistories()->max('sequence')) + 1,
                'status_at' => now(),
            ]);
        });

        $order->refresh();

        return response()->json([
            'message' => 'Status order seller berhasil diperbarui.',
            'data' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'status_code' => $order->statusCode(),
                'status_label' => $this->formatStatusLabel($order->status),
                'cancellation_reason' => $order->statusCode() === Transaction::STATUS_CODE_CANCELED ? [
                    'category_id' => $order->cancellation_reason_category_id,
                    'category_name' => $cancellationReasonCategory?->name,
                    'allows_free_text' => $cancellationReasonCategory?->allows_free_text,
                    'reason_text' => $order->cancellation_reason_text,
                ] : null,
            ],
        ]);
    }

    private function statusTitle(string $status): string
    {
        return match ($status) {
            Transaction::STATUS_ACCEPTED_BY_STORE => 'Pesanan diterima',
            Transaction::STATUS_PROCESSING => 'Pesanan sedang diproses',
            Transaction::STATUS_ON_THE_WAY => 'Pesanan dalam perjalanan',
            Transaction::STATUS_READY_FOR_PICKUP => 'Pesanan siap diambil',
            Transaction::STATUS_COMPLETED => 'Pesanan selesai',
            Transaction::STATUS_CANCELED => 'Pesanan dibatalkan',
            default => ucfirst($status),
        };
    }

    private function statusDescription(string $status, ?CancellationReasonCategory $cancellationReasonCategory = null, ?string $cancellationReasonText = null): string
    {
        if ($status === Transaction::STATUS_CANCELED && $cancellationReasonCategory) {
            return trim(sprintf(
                'Pesanan dibatalkan. Alasan: %s%s',
                $cancellationReasonCategory->name,
                $cancellationReasonText ? ' - '.$cancellationReasonText : '',
            ));
        }

        return match ($status) {
            Transaction::STATUS_ACCEPTED_BY_STORE => 'Pesanan telah diterima oleh toko',
            Transaction::STATUS_PROCESSING => 'Pesanan sedang diproses oleh toko',
            Transaction::STATUS_ON_THE_WAY => 'Pesanan sedang dalam perjalanan',
            Transaction::STATUS_READY_FOR_PICKUP => 'Pesanan siap diambil di toko',
            Transaction::STATUS_COMPLETED => 'Pesanan telah selesai',
            Transaction::STATUS_CANCELED => 'Pesanan dibatalkan',
            default => ucfirst($status),
        };
    }

    private function formatStatusLabel(string $status): string
    {
        return match ($status) {
            Transaction::STATUS_PENDING_PAYMENT => 'Menunggu Pembayaran',
            Transaction::STATUS_ACCEPTED_BY_STORE => 'Diterima Toko',
            Transaction::STATUS_PROCESSING => 'Sedang Diproses',
            Transaction::STATUS_ON_THE_WAY => 'Dalam Perjalanan',
            Transaction::STATUS_READY_FOR_PICKUP => 'Siap Diambil',
            Transaction::STATUS_COMPLETED => 'Pesanan Selesai',
            Transaction::STATUS_CANCELED => 'Pesanan Dibatalkan',
            default => ucfirst($status),
        };
    }

    private function ensureAllowedTransition(Transaction $order, string $targetStatusCode): void
    {
        if ($targetStatusCode === Transaction::STATUS_CODE_CANCELED) {
            return;
        }

        $currentStatusCode = $order->statusCode();
        $allowedTargets = match ($currentStatusCode) {
            Transaction::STATUS_CODE_PENDING_PAYMENT => [Transaction::STATUS_CODE_ACCEPTED_BY_STORE],
            Transaction::STATUS_CODE_ACCEPTED_BY_STORE => [Transaction::STATUS_CODE_PROCESSING],
            Transaction::STATUS_CODE_PROCESSING => $order->delivery_method_code === 'pickup'
                ? [Transaction::STATUS_CODE_READY_FOR_PICKUP]
                : [Transaction::STATUS_CODE_ON_THE_WAY],
            Transaction::STATUS_CODE_ON_THE_WAY,
            Transaction::STATUS_CODE_READY_FOR_PICKUP => [Transaction::STATUS_CODE_COMPLETED],
            default => [],
        };

        if (! in_array($targetStatusCode, $allowedTargets, true)) {
            throw ValidationException::withMessages([
                'status_code' => ['Transisi status order tidak valid.'],
            ]);
        }
    }
}
