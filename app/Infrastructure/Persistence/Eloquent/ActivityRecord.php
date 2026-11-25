<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One row of `activities`.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property Carbon $occurred_at
 */
#[Unguarded]
#[Table(name: 'activities')]
#[WithoutTimestamps]
final class ActivityRecord extends Model
{
    /**
     * Casts the time of occurrence.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['occurred_at' => 'immutable_datetime'];
    }
}
