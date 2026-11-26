<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Accounts;

use App\Domain\Account\PhoneType;
use App\Infrastructure\Persistence\Eloquent\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Override;

/**
 * The accounts in the admin panel (the 2015 `Gestao de Usuarios.php`): a list searched by id, name,
 * email or CPF, and the edit page.
 *
 * The list reads the rows; every change goes through a use case (AccountActions, EditAccount), so the
 * panel follows the same rules as the owner's own pages. Closed accounts are not listed, as the
 * repository never finds them, and accounts are created by signing up only.
 *
 * @extends resource<User>
 */
final class AccountResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $slug = 'accounts';

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $isGloballySearchable = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    /**
     * "Account", as the panel names one.
     */
    #[Override]
    public static function getModelLabel(): string
    {
        return __('admin.accounts.label');
    }

    /**
     * "Accounts", as the navigation and the list name them.
     */
    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('admin.accounts.plural_label');
    }

    /**
     * The open accounts only.
     *
     * @return Builder<User>
     */
    #[Override]
    public static function getEloquentQuery(): Builder
    {
        return User::query()->whereNull('closed_at');
    }

    /**
     * No account is made here: a person signs up, and gets the initial licence that way.
     */
    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * The details an administrator may change, the name and the phone, as on the owner's profile; the
     * CPF and the email are shown and fixed.
     */
    #[Override]
    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label(__('accounts.fields.name'))->required(),
            TextInput::make('phone_number')->label(__('accounts.fields.phone_number'))->required(),
            Select::make('phone_type')
                ->label(__('accounts.fields.phone_type'))
                ->options(self::phoneTypes())
                ->required(),
            TextInput::make('cpf')->label(__('accounts.fields.cpf'))->disabled()->dehydrated(false),
            TextInput::make('email')->label(__('accounts.fields.email'))->disabled()->dehydrated(false),
        ]);
    }

    /**
     * The list: who, how they are reached, the role and whether the account is in use.
     */
    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label(__('admin.accounts.columns.id'))->sortable()->searchable(),
                TextColumn::make('name')->label(__('accounts.fields.name'))->sortable()->searchable(),
                TextColumn::make('email')->label(__('accounts.fields.email'))->searchable(),
                TextColumn::make('cpf')
                    ->label(__('accounts.fields.cpf'))
                    ->searchable(query: AccountSearch::byCpf(...)),
                TextColumn::make('role')
                    ->label(__('admin.accounts.columns.role'))
                    ->formatStateUsing(static fn (string $state): string => __("admin.roles.{$state}")),
                TextColumn::make('status')
                    ->label(__('admin.accounts.columns.status'))
                    ->badge()
                    ->state(static fn (User $record): string => AccountSearch::status($record))
                    ->formatStateUsing(static fn (string $state): string => __("admin.statuses.{$state}"))
                    ->color(static fn (string $state): string => $state === AccountSearch::ACTIVE ? 'success' : 'warning'),
            ])
            ->defaultSort('name')
            ->recordActions([
                EditAction::make(),
                AccountActions::deactivate(),
                AccountActions::reactivate(),
                AccountActions::close(),
            ]);
    }

    /**
     * The list and the edit page; there is no create page.
     */
    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListAccounts::route('/'),
            'edit' => EditAccount::route('/{record}/edit'),
        ];
    }

    /**
     * The kinds of phone, each with its label.
     *
     * @return array<string, string>
     */
    private static function phoneTypes(): array
    {
        $types = [];

        foreach (PhoneType::cases() as $type) {
            $types[$type->value] = __("accounts.phone_types.{$type->value}");
        }

        return $types;
    }
}
