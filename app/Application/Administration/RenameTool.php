<?php

declare(strict_types=1);

namespace App\Application\Administration;

use App\Domain\Licensing\CatalogueName;
use App\Domain\Licensing\InvalidCatalogueName;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\ToolNameTaken;
use App\Domain\Licensing\ToolRepository;
use App\Domain\Licensing\UnknownTool;

/**
 * Renames a tool from the admin panel. A tool is code (a calculator, the converter), so the panel names
 * the tools but does not add or remove them: a new tool arrives with its code and a migration.
 */
final readonly class RenameTool
{
    public function __construct(
        private ToolRepository $tools,
    ) {}

    /**
     * Stores the new name.
     *
     * @throws UnknownTool When no tool has the identifier.
     * @throws InvalidCatalogueName When the name is blank or too long.
     * @throws ToolNameTaken When another tool has the name.
     */
    public function handle(string $id, string $name): void
    {
        $this->tools->rename(ToolId::from($id), new CatalogueName($name));
    }
}
