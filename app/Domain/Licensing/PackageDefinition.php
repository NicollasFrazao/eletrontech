<?php

declare(strict_types=1);

namespace App\Domain\Licensing;

/**
 * The name and the tools of a package as the admin panel writes them. The everyday tools
 * (`Package::ESSENTIALS`) are always added, as every package carries them.
 */
final readonly class PackageDefinition
{
    /**
     * The tools, the essentials first, without repeats.
     *
     * @var non-empty-list<ToolId>
     */
    public array $tools;

    /**
     * @param  list<ToolId>  $picked  The tools chosen besides the essentials; at least one other.
     *
     * @throws PackageWithoutTools When no tool other than the essentials was chosen.
     */
    public function __construct(
        public CatalogueName $name,
        array $picked,
    ) {
        $tools = [];

        foreach (ToolId::essentials() as $essential) {
            $tools[$essential->value] = $essential;
        }

        $essentials = count($tools);

        foreach ($picked as $tool) {
            $tools[$tool->value] = $tool;
        }

        if (count($tools) === $essentials) {
            throw PackageWithoutTools::create();
        }

        $this->tools = array_values($tools);
    }
}
