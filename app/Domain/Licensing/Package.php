<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * A set of tools a licence gives access to (the 2015 `tb_pacote` and `pacote_ferramenta`).
 */
final readonly class Package
{
    /**
     * The tools, keyed by identifier.
     *
     * @var non-empty-array<string, ToolId>
     */
    private array $tools;

    /**
     * @param  non-empty-list<ToolId>  $tools  The tools of the package, at least one.
     */
    public function __construct(
        public PackageId $id,
        public string $name,
        public PackageKind $kind,
        array $tools,
    ) {
        $byId = [];

        foreach ($tools as $tool) {
            $byId[$tool->value] = $tool;
        }

        $this->tools = $byId;
    }

    /**
     * Whether the package gives access to the tool.
     */
    public function includes(ToolId $tool): bool
    {
        return isset($this->tools[$tool->value]);
    }

    /**
     * The tools, in the order they were given, without repeats.
     *
     * @return non-empty-list<ToolId>
     */
    public function tools(): array
    {
        return array_values($this->tools);
    }

    /**
     * Whether a licence for the other package is a licence for this one: the same package with the same
     * tools (a custom package changes its tools when it is bought again).
     */
    public function isSameAs(self $other): bool
    {
        return $this->id->value === $other->id->value
            && array_keys($this->tools) === array_keys($other->tools);
    }
}
