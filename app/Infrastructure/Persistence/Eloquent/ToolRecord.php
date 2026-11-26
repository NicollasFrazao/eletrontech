<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One row of `tools`, with the packages that hold it through `package_tool`.
 *
 * @property string $id
 * @property string $name
 */
#[Unguarded]
#[Table(name: 'tools', keyType: 'string', incrementing: false)]
#[WithoutTimestamps]
final class ToolRecord extends Model
{
    /**
     * The packages that hold the tool, custom ones included.
     *
     * @return BelongsToMany<PackageRecord, $this>
     */
    public function packages(): BelongsToMany
    {
        return $this->belongsToMany(PackageRecord::class, 'package_tool', 'tool_id', 'package_id');
    }
}
