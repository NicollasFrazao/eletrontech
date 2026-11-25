<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

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
final class ActivityRecord extends Model
{
    /**
     * The rows carry their own time of occurrence.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table the log is kept in.
     *
     * @var string|null
     */
    protected $table = 'activities';

    /**
     * Every column is written by EloquentActivityLog, never from a request.
     *
     * @var array<string>
     */
    protected $guarded = [];

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
