<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * Something a licence gives access to: a calculator, the unit converter or the floor-plan editor (the
 * 2015 `tb_ferramenta`).
 */
final readonly class Tool
{
    public function __construct(
        public ToolId $id,
        public string $name,
    ) {}
}
