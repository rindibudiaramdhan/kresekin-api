<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSellerOrderStatusRequest;
use App\Models\Transaction;
use App\Models\TransactionStatusHistory;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UpdateSellerOrderStatusController extends Controller
{
    public function __invoke(UpdateSellerOrderStatusRequest $request, int $id): JsonResponse
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

        DB::transaction(function () use ($order, $status, $validated): void {
            $order->forceFill([
                'status' => $status,
            ])->save();

            TransactionStatusHistory::query()->create([
                'transaction_id' => $order->id,
                'status' => $status,
                'title' => $this->statusTitle($status),
                'description' => $validated['description'] ?? $this->statusDescription($status),
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
            ],
        ]);
    }

    private function statusTitle(string $status): string
    {
        return match ($status) {
            Transaction::STATUS_ACCEPTED_BY_STORE => 'Pesanan diterima',
            Transaction::STATUS_PROCESSING => 'Pesanan sedang diproses',
            Transaction::STATUS_ON_THE_WAY => 'Pesanan dalam perjalanan',
            Transaction::STATUS_COMPLETED => 'Pesanan selesai',
            Transaction::STATUS_CANCELED => 'Pesanan dibatalkan',
            default => ucfirst($status),
        };
    }

    private function statusDescription(string $status): string
    {
        return match ($status) {
            Transaction::STATUS_ACCEPTED_BY_STORE => 'Pesanan telah diterima oleh toko',
            Transaction::STATUS_PROCESSING => 'Pesanan sedang diproses oleh toko',
            Transaction::STATUS_ON_THE_WAY => 'Pesanan sedang dalam perjalanan',
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
            Transaction::STATUS_COMPLETED => 'Pesanan Selesai',
            Transaction::STATUS_CANCELED => 'Pesanan Dibatalkan',
            default => ucfirst($status),
        };
    }
}
