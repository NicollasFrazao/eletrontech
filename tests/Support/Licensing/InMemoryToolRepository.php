<?php

declare(strict_types=1);

namespace Tests\Support\Licensing;

use App\Domain\Licensing\Tool;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\ToolRepository;

/**
 * Tools kept in memory: ohms-law, unit-converter, general-power and basic-calculator unless others are given.
 */
final class InMemoryToolRepository implements ToolRepository
{
    /**
     * @var array<string, Tool> Keyed by identifier.
     */
    private array $tools = [];

    public function __construct(string ...$ids)
    {
        foreach ($ids === [] ? ['ohms-law', 'unit-converter', 'general-power', 'basic-calculator'] : $ids as $id) {
            $this->tools[$id] = new Tool(ToolId::from($id), ucfirst(str_replace('-', ' ', $id)));
        }
    }

    public function all(): array
    {
        $tools = array_values($this->tools);
        usort($tools, static fn (Tool $a, Tool $b): int => strcmp($a->name, $b->name));

        return $tools;
    }

    public function named(array $ids): array
    {
        return array_values(array_filter(array_map(fn (ToolId $id): ?Tool => $this->tools[$id->value] ?? null, $ids)));
    }

    public function exists(ToolId $id): bool
    {
        return isset($this->tools[$id->value]);
    }
}
