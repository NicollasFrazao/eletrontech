<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;

/**
 * One row of `files`.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $folder_id
 * @property string $name
 * @property string $type
 * @property int $size
 * @property string $content_key
 * @property CarbonImmutable $created_at
 */
#[Unguarded]
#[Table(name: 'files')]
final class FileRecord extends Model
{
    /**
     * Casts the size and the creation date.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['size' => 'integer', 'created_at' => 'immutable_datetime'];
    }
}
