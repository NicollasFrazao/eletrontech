<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Packages;

use App\Domain\Licensing\PackageKind;
use App\Infrastructure\Persistence\Eloquent\PackageRecord;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Resources\Resource as FilamentResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

/**
 * The packages in the admin panel (the 2015 `tb_pacote` and `pacote_ferramenta`): the free package and
 * the ready-made ones the shop sells, each with its name, its tools, its price and its days.
 *
 * The list reads the rows; creating and changing go through the use cases (CreatePackage,
 * ChangePackage), so the licensing rules hold here as in the shop. A person's custom package is theirs
 * and is not listed. Nothing is deleted: a licence may hold the package.
 *
 * @extends FilamentResource<PackageRecord>
 */
final class PackageResource extends FilamentResource
{
    protected static ?string $model = PackageRecord::class;

    protected static ?string $slug = 'packages';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    /**
     * "Package", as the panel names one.
     */
    #[Override]
    public static function getModelLabel(): string
    {
        return __('admin.packages.label');
    }

    /**
     * "Packages", as the navigation and the list name them.
     */
    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('admin.packages.plural_label');
    }

    /**
     * The free and the ready-made packages, not the custom ones people made.
     *
     * @return Builder<PackageRecord>
     */
    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return PackageRecord::query()->whereNull('owner_id');
    }

    /**
     * The name, the tools, and the price and days of a package that is sold.
     */
    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components(PackageForm::components());
    }

    /**
     * The list: each package with its kind, how many tools it has, its price and its days.
     */
    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('admin.packages.fields.name'))->sortable()->searchable(),
                TextColumn::make('kind')
                    ->label(__('admin.packages.fields.kind'))
                    ->badge()
                    ->formatStateUsing(static fn (string $state): string => __("admin.packages.kinds.{$state}"))
                    ->color(static fn (string $state): string => $state === PackageKind::Free->value ? 'gray' : 'primary'),
                TextColumn::make('tools_count')->label(__('admin.packages.fields.tools'))->counts('tools'),
                TextColumn::make('price')->label(__('admin.packages.fields.price'))->money('BRL'),
                TextColumn::make('days')->label(__('admin.packages.fields.days')),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /**
     * The list, the create page and the edit page.
     */
    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListPackages::route('/'),
            'create' => CreatePackage::route('/create'),
            'edit' => EditPackage::route('/{record}/edit'),
        ];
    }
}
