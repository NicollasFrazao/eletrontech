<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

/**
 * One row of `folders`.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $parent_id
 * @property string $name
 * @property CarbonImmutable $created_at
 */
#[Unguarded]
#[Table(name: 'folders')]
final class FolderRecord extends Model
{
    /**
     * Casts the creation date.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
