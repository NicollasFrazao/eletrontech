<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * The tools a package can hold, with the names people know them by.
 */
interface ToolRepository
{
    /**
     * Every tool, by name.
     *
     * @return list<Tool>
     */
    public function all(): array;

    /**
     * The tools with these identifiers, in the order given; unknown ones are left out.
     *
     * @param  list<ToolId>  $ids
     *
     * @return list<Tool>
     */
    public function named(array $ids): array;

    /**
     * Whether a tool has the identifier.
     */
    public function exists(ToolId $id): bool;

    /**
     * Gives the tool another name.
     *
     * @throws UnknownTool When no tool has the identifier.
     * @throws ToolNameTaken When another tool has the name.
     */
    public function rename(ToolId $id, CatalogueName $name): void;
}
