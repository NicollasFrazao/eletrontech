<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Model;

/**
 * One row of `tools`.
 *
 * @property string $id
 * @property string $name
 */
#[Unguarded]
#[Table(name: 'tools', keyType: 'string', incrementing: false)]
#[WithoutTimestamps]
final class ToolRecord extends Model {}
