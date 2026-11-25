<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

/**
 * One row of `tool_uses`.
 *
 * @property int $id
 * @property int $user_id
 * @property string $tool_id
 * @property CarbonImmutable $used_at
 */
#[Unguarded]
#[Table(name: 'tool_uses')]
#[WithoutTimestamps]
final class ToolUseRecord extends Model
{
    /**
     * Casts the time of use.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['used_at' => 'immutable_datetime'];
    }
}
