<?php

namespace App\Support;

use App\Models\TransactionRating;

class TransactionRatingResponseMapper
{
    public static function map(TransactionRating $rating): array
    {
        return [
            'id' => $rating->id,
            'transaction_id' => $rating->transaction_id,
            'rating' => $rating->rating,
            'comment' => $rating->comment,
            'created_at' => $rating->created_at?->toIso8601String(),
            'updated_at' => $rating->updated_at?->toIso8601String(),
        ];
    }
}
