<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row of `licences`: a person's licence, keyed by the person.
 *
 * @property int $user_id
 * @property int $package_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property-read PackageRecord $package
 */
#[Unguarded]
#[Table(name: 'licences', key: 'user_id', incrementing: false)]
final class LicenceRecord extends Model
{
    /**
     * The package the licence is for.
     *
     * @return BelongsTo<PackageRecord, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(PackageRecord::class, 'package_id');
    }

    /**
     * Casts the period.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }
}
