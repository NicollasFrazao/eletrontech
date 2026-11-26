<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Tools;

use App\Infrastructure\Persistence\Eloquent\ToolRecord;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource as FilamentResource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Override;

/**
 * The tools in the admin panel (the 2015 `tb_ferramenta`): each with its identifier and the name the
 * shop and the licence page show.
 *
 * A tool is code (a calculator, the converter), so the panel renames tools but does not add or delete
 * them: a new tool arrives with its code and a migration. The name is saved by the RenameTool use case.
 *
 * @extends FilamentResource<ToolRecord>
 */
final class ToolResource extends FilamentResource
{
    protected static ?string $model = ToolRecord::class;

    protected static ?string $slug = 'tools';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    /**
     * "Tool", as the panel names one.
     */
    #[Override]
    public static function getModelLabel(): string
    {
        return __('admin.tools.label');
    }

    /**
     * "Tools", as the navigation and the list name them.
     */
    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('admin.tools.plural_label');
    }

    /**
     * No tool is made here: it would have no code behind it.
     */
    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * The name; the identifier is the code's and is shown fixed.
     */
    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('id')->label(__('admin.tools.fields.id'))->disabled()->dehydrated(false),
            TextInput::make('name')->label(__('admin.tools.fields.name'))->required(),
        ]);
    }

    /**
     * The list: each tool with its identifier and how many packages hold it.
     */
    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(__('admin.tools.fields.name'))->sortable()->searchable(),
                TextColumn::make('id')->label(__('admin.tools.fields.id'))->searchable(),
                TextColumn::make('packages_count')->label(__('admin.tools.fields.packages'))->counts('packages'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    /**
     * The list and the edit page; there is no create page.
     */
    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListTools::route('/'),
            'edit' => EditTool::route('/{record}/edit'),
        ];
    }
}
