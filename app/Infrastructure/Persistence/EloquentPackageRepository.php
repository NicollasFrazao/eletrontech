<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Account\AccountId;
use App\Domain\Licensing\Package;
use App\Domain\Licensing\PackageDefinition;
use App\Domain\Licensing\PackageId;
use App\Domain\Licensing\PackageKind;
use App\Domain\Licensing\PackageRepository;
use App\Domain\Licensing\PackageTerms;
use App\Domain\Licensing\ToolId;
use App\Domain\Licensing\UnknownPackage;
use App\Domain\Licensing\UnknownTool;
use App\Infrastructure\Persistence\Eloquent\PackageRecord;
use App\Infrastructure\Persistence\Eloquent\ToolRecord;
use Illuminate\Database\ConnectionInterface;
use UnexpectedValueException;
use ValueError;

/**
 * The packages kept in `packages` and `package_tool`.
 */
final readonly class EloquentPackageRepository implements PackageRepository
{
    /**
     * The name every custom package has, as in 2015 ("Eletron Tech Custom Pack").
     */
    private const string CUSTOM_NAME = 'Custom';

    public function __construct(
        private ConnectionInterface $connection,
    ) {}

    /**
     * The package with its tools.
     *
     * @throws UnknownPackage When no row has the id.
     */
    public function get(PackageId $id): Package
    {
        $record = PackageRecord::query()->with('tools')->find($id->value) ?? throw UnknownPackage::withId($id->value);

        return $this->package($record);
    }

    /**
     * The one package of the free kind.
     *
     * @throws UnexpectedValueException When the migrations did not insert it.
     */
    public function free(): Package
    {
        $record = PackageRecord::query()->with('tools')->where('kind', PackageKind::Free->value)->first()
            ?? throw new UnexpectedValueException('There is no free package; the migrations insert it.');

        return $this->package($record);
    }

    /**
     * The ready-made packages, by name.
     *
     * @return list<Package>
     */
    public function readyMade(): array
    {
        return array_values(
            PackageRecord::query()
                ->with('tools')
                ->where('kind', PackageKind::ReadyMade->value)
                ->orderBy('name')
                ->get()
                ->map($this->package(...))
                ->all(),
        );
    }

    /**
     * Replaces the tools of the owner's custom package, creating it the first time, in one transaction.
     *
     * @param  non-empty-list<ToolId>  $tools
     *
     * @throws UnknownTool When a tool does not exist.
     */
    public function customFor(AccountId $owner, array $tools): Package
    {
        $ids = $this->knownTools($tools);
        $record = $this->connection->transaction(static function () use ($owner, $ids): PackageRecord {
            $record = PackageRecord::query()->firstOrCreate(
                ['owner_id' => $owner->value],
                ['name' => self::CUSTOM_NAME, 'kind' => PackageKind::Custom->value],
            );
            $record->tools()->sync($ids);

            return $record;
        });

        return $this->package($record->load('tools'));
    }

    /**
     * Inserts a ready-made package and its tools in one transaction.
     *
     * @throws UnknownTool When a tool does not exist.
     * @throws UnexpectedValueException When the database hands back an id that is not positive.
     */
    public function add(PackageDefinition $definition, PackageTerms $terms): PackageId
    {
        $ids = $this->knownTools($definition->tools);

        $record = $this->connection->transaction(static function () use ($definition, $terms, $ids): PackageRecord {
            $record = PackageRecord::query()->create([
                'name' => $definition->name->value,
                'kind' => PackageKind::ReadyMade->value,
                ...self::termColumns($terms),
            ]);
            $record->tools()->sync($ids);

            return $record;
        });

        try {
            return new PackageId($record->id);
        } catch (UnknownPackage $error) {
            throw new UnexpectedValueException("The new package got the id {$record->id}, which no package can have.", 0, $error);
        }
    }

    /**
     * Updates the package's row and replaces its tools in one transaction.
     *
     * @throws UnknownPackage When no free or ready-made package has the id.
     * @throws UnknownTool When a tool does not exist.
     */
    public function change(PackageId $id, PackageDefinition $definition, ?PackageTerms $terms): void
    {
        $record = PackageRecord::query()->whereNull('owner_id')->find($id->value) ?? throw UnknownPackage::withId($id->value);
        $ids = $this->knownTools($definition->tools);

        $this->connection->transaction(static function () use ($record, $definition, $terms, $ids): void {
            $record->update(['name' => $definition->name->value, ...self::termColumns($terms)]);
            $record->tools()->sync($ids);
        });
    }

    /**
     * The identifiers of the tools, without repeats, once each is known to exist.
     *
     * @param  list<ToolId>  $tools
     *
     * @return list<string>
     *
     * @throws UnknownTool When a tool does not exist.
     */
    private function knownTools(array $tools): array
    {
        $ids = array_values(array_unique(array_map(static fn (ToolId $tool): string => $tool->value, $tools)));
        $known = ToolRecord::query()->whereIn('id', $ids)->get()->map(static fn (ToolRecord $tool): string => $tool->id)->all();
        $missing = array_values(array_diff($ids, $known));

        if ($missing !== []) {
            throw UnknownTool::withId($missing[0]);
        }

        return $ids;
    }

    /**
     * The price and days columns of the terms; both null for a package that is not sold.
     *
     * @return array{price: string|null, days: int|null}
     */
    private static function termColumns(?PackageTerms $terms): array
    {
        return [
            'price' => $terms?->price->amount->__toString(),
            'days' => $terms?->days->value,
        ];
    }

    /**
     * The package a row stands for.
     *
     * @throws UnexpectedValueException When the row has no tool or holds a value no migration writes.
     */
    private function package(PackageRecord $record): Package
    {
        try {
            $tools = array_values($record->tools->map(static fn (ToolRecord $tool): ToolId => ToolId::from($tool->id))->all());

            if ($tools === []) {
                throw new UnexpectedValueException("The package {$record->id} has no tool.");
            }

            return new Package(new PackageId($record->id), $record->name, PackageKind::from($record->kind), $tools);
        } catch (UnknownPackage|UnknownTool|ValueError $error) {
            throw new UnexpectedValueException("The package {$record->id} holds a value no migration writes.", 0, $error);
        }
    }
}
