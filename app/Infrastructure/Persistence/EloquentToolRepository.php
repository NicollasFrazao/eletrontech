<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Licensing\Tool;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\ToolRepository;
use App\Domain\Licensing\UnknownTool;
use App\Infrastructure\Persistence\Eloquent\ToolRecord;
use UnexpectedValueException;

/**
 * The tools kept in `tools`.
 */
final readonly class EloquentToolRepository implements ToolRepository
{
    /**
     * Every tool, by name.
     *
     * @return list<Tool>
     */
    public function all(): array
    {
        return array_values(ToolRecord::query()->orderBy('name')->get()->map($this->tool(...))->all());
    }

    /**
     * The tools with these identifiers, in the order given; unknown ones are left out.
     *
     * @param  list<ToolId>  $ids
     *
     * @return list<Tool>
     */
    public function named(array $ids): array
    {
        $records = ToolRecord::query()
            ->whereIn('id', array_map(static fn (ToolId $id): string => $id->value, $ids))
            ->get()
            ->keyBy('id');

        return array_values(array_filter(array_map(
            static fn (ToolId $id): ?Tool => ($record = $records->get($id->value)) instanceof ToolRecord
                ? new Tool($id, $record->name)
                : null,
            $ids,
        )));
    }

    /**
     * Whether a row has the identifier.
     */
    public function exists(ToolId $id): bool
    {
        return ToolRecord::query()->whereKey($id->value)->exists();
    }

    /**
     * The tool a row stands for.
     *
     * @throws UnexpectedValueException When the row's identifier cannot be a tool's.
     */
    private function tool(ToolRecord $record): Tool
    {
        try {
            return new Tool(ToolId::from($record->id), $record->name);
        } catch (UnknownTool $error) {
            throw new UnexpectedValueException("The tool \"{$record->id}\" has an identifier no migration writes.", 0, $error);
        }
    }
}
