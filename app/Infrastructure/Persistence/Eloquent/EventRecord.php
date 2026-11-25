<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

/**
 * One row of `events`.
 *
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property CarbonImmutable $starts_at
 * @property string $description
 */
#[Unguarded]
#[Table(name: 'events')]
final class EventRecord extends Model
{
    /**
     * Casts the moment.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime'];
    }
}
