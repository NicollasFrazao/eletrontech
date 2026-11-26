<?php

declare(strict_types=1);

namespace Tests\Support\Licensing;

use App\Domain\Licensing\CatalogueName;
use App\Domain\Licensing\Tool;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\ToolNameTaken;
use App\Domain\Licensing\ToolRepository;
use App\Domain\Licensing\UnknownTool;

/**
 * Tools kept in memory: ohms-law, unit-converter, general-power, basic-calculator and rule-of-three unless
 * others are given.
 */
final class InMemoryToolRepository implements ToolRepository
{
    /**
     * @var array<string, Tool> Keyed by identifier.
     */
    private array $tools = [];

    public function __construct(string ...$ids)
    {
        foreach ($ids === [] ? ['ohms-law', 'unit-converter', 'general-power', 'basic-calculator', 'rule-of-three'] : $ids as $id) {
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

    public function rename(ToolId $id, CatalogueName $name): void
    {
        if (! $this->exists($id)) {
            throw UnknownTool::withId($id->value);
        }

        foreach ($this->tools as $tool) {
            if ($tool->name === $name->value && $tool->id->value !== $id->value) {
                throw ToolNameTaken::named($name);
            }
        }

        $this->tools[$id->value] = new Tool($id, $name->value);
    }
}
