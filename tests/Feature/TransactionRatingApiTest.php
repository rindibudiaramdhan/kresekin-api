<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\UserSessionToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRatingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_rate_completed_own_transaction_and_get_the_rating(): void
    {
        [$buyer, $token] = $this->createAuthenticatedUser('rating-success-token');
        $transaction = $this->createTransaction($buyer, Transaction::STATUS_COMPLETED, 'RATING001');
        $endpoint = '/api/users/transactions/'.$transaction->id.'/rating';

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson($endpoint, [
                'rating' => 5,
                'comment' => 'Pesanan sesuai dan cepat.',
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Rating pesanan berhasil disimpan.')
            ->assertJsonPath('data.transaction_id', $transaction->id)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Pesanan sesuai dan cepat.');

        $this->assertDatabaseHas('transaction_ratings', [
            'transaction_id' => $transaction->id,
            'user_id' => $buyer->id,
            'rating' => 5,
            'comment' => 'Pesanan sesuai dan cepat.',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson($endpoint)
            ->assertOk()
            ->assertJsonPath('message', 'Rating pesanan berhasil diambil.')
            ->assertJsonPath('data.id', $response->json('data.id'))
            ->assertJsonPath('data.rating', 5);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users/transactions/'.$transaction->id)
            ->assertOk()
            ->assertJsonPath('data.can_rate', false)
            ->assertJsonPath('data.rating.rating', 5);
    }

    public function test_rating_comment_is_optional(): void
    {
        [$buyer, $token] = $this->createAuthenticatedUser('rating-no-comment-token');
        $transaction = $this->createTransaction($buyer, Transaction::STATUS_COMPLETED, 'RATING002');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users/transactions/'.$transaction->id.'/rating', ['rating' => 4])
            ->assertCreated()
            ->assertJsonPath('data.comment', null);
    }

    public function test_buyer_cannot_rate_transaction_before_it_is_completed(): void
    {
        [$buyer, $token] = $this->createAuthenticatedUser('rating-status-token');
        $transaction = $this->createTransaction($buyer, Transaction::STATUS_PROCESSING, 'RATING003');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/users/transactions/'.$transaction->id.'/rating', ['rating' => 5])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status'])
            ->assertJsonPath('errors.status.0', 'Rating hanya dapat diberikan setelah pesanan selesai.');

        $this->assertDatabaseMissing('transaction_ratings', [
            'transaction_id' => $transaction->id,
        ]);
    }

    public function test_buyer_cannot_rate_the_same_transaction_twice(): void
    {
        [$buyer, $token] = $this->createAuthenticatedUser('rating-duplicate-token');
        $transaction = $this->createTransaction($buyer, Transaction::STATUS_COMPLETED, 'RATING004');
        $endpoint = '/api/users/transactions/'.$transaction->id.'/rating';

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson($endpoint, ['rating' => 4])
            ->assertCreated();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson($endpoint, ['rating' => 2])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['rating'])
            ->assertJsonPath('errors.rating.0', 'Pesanan ini sudah pernah diberi rating.');

        $this->assertSame(1, $transaction->rating()->count());
        $this->assertSame(4, $transaction->rating()->firstOrFail()->rating);
    }

    public function test_rating_only_accepts_integers_between_one_and_five(): void
    {
        [$buyer, $token] = $this->createAuthenticatedUser('rating-validation-token');
        $transaction = $this->createTransaction($buyer, Transaction::STATUS_COMPLETED, 'RATING005');
        $endpoint = '/api/users/transactions/'.$transaction->id.'/rating';

        foreach ([0, 6, 4.5] as $invalidRating) {
            $this->withHeader('Authorization', 'Bearer '.$token)
                ->postJson($endpoint, ['rating' => $invalidRating])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['rating']);
        }
    }

    public function test_other_buyers_transaction_is_hidden_for_rating_write_and_read(): void
    {
        [, $token] = $this->createAuthenticatedUser('rating-ownership-token');
        [$otherBuyer] = $this->createAuthenticatedUser('rating-other-owner-token');
        $transaction = $this->createTransaction($otherBuyer, Transaction::STATUS_COMPLETED, 'RATING006');
        $endpoint = '/api/users/transactions/'.$transaction->id.'/rating';

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson($endpoint, ['rating' => 5])
            ->assertNotFound()
            ->assertJsonPath('message', 'Transaksi tidak ditemukan.');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson($endpoint)
            ->assertNotFound()
            ->assertJsonPath('message', 'Transaksi tidak ditemukan.');
    }

    public function test_rating_endpoints_require_buyer_authentication_and_role(): void
    {
        [$user, $token] = $this->createAuthenticatedUser('rating-role-token');
        $transaction = $this->createTransaction($user, Transaction::STATUS_COMPLETED, 'RATING007');
        $endpoint = '/api/users/transactions/'.$transaction->id.'/rating';

        $this->postJson($endpoint, ['rating' => 5])->assertUnauthorized();
        $this->getJson($endpoint)->assertUnauthorized();

        $user->forceFill(['role' => User::ROLE_SELLER])->save();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson($endpoint, ['rating' => 5])
            ->assertForbidden();
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson($endpoint)
            ->assertForbidden();
    }

    public function test_get_returns_not_found_when_transaction_has_not_been_rated(): void
    {
        [$buyer, $token] = $this->createAuthenticatedUser('rating-missing-token');
        $transaction = $this->createTransaction($buyer, Transaction::STATUS_COMPLETED, 'RATING008');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users/transactions/'.$transaction->id.'/rating')
            ->assertNotFound()
            ->assertJsonPath('message', 'Rating pesanan belum tersedia.');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/users/transactions/'.$transaction->id)
            ->assertOk()
            ->assertJsonPath('data.can_rate', true)
            ->assertJsonPath('data.rating', null);
    }

    private function createAuthenticatedUser(string $plainTextToken): array
    {
        $user = User::query()->create([
            'name' => 'Buyer Rating',
            'email' => $plainTextToken.'@example.com',
            'phone' => '+62'.substr(hash('crc32b', $plainTextToken), 0, 12),
            'type' => 'phone',
        ]);

        UserSessionToken::query()->create([
            'user_id' => $user->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return [$user, $plainTextToken];
    }

    private function createTransaction(User $buyer, string $status, string $orderNumber): Transaction
    {
        return Transaction::query()->create([
            'user_id' => $buyer->id,
            'order_number' => $orderNumber,
            'status' => $status,
            'transaction_at' => now(),
        ]);
    }
}
