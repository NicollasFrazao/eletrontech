<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One row of `packages`, with its tools through `package_tool`.
 *
 * @property int $id
 * @property string $name
 * @property string $kind
 * @property string|null $price
 * @property int|null $days
 * @property int|null $owner_id
 * @property-read Collection<int, ToolRecord> $tools
 */
#[Unguarded]
#[Table(name: 'packages')]
final class PackageRecord extends Model
{
    /**
     * The tools of the package.
     *
     * @return BelongsToMany<ToolRecord, $this>
     */
    public function tools(): BelongsToMany
    {
        return $this->belongsToMany(ToolRecord::class, 'package_tool', 'package_id', 'tool_id')->orderBy('tools.id');
    }
}
