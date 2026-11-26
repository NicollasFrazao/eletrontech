<?php

declare(strict_types=1);

namespace App\Application\Licensing;

use App\Domain\Licensing\Tool;

/**
 * A package of the shop with the names of its tools.
 */
final readonly class PackageView
{
    /**
     * @param  list<Tool>  $tools
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $tools,
    ) {}
}
