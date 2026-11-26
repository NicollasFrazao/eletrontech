<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of `feedback`, with the account that sent it.
 *
 * @property int $id
 * @property int $user_id
 * @property string $subject
 * @property string $message
 * @property CarbonImmutable $sent_at
 */
#[Unguarded]
#[Table(name: 'feedback')]
#[WithoutTimestamps]
final class FeedbackRecord extends Model
{
    /**
     * The account that sent the message.
     *
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Casts the moment it was sent.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['sent_at' => 'immutable_datetime'];
    }
}
