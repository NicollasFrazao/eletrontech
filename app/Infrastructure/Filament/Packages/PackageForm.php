<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Packages;

use App\Application\Administration\PackageInput;
use App\Domain\Licensing\InvalidCatalogueName;
use App\Domain\Licensing\InvalidPrice;
use App\Domain\Licensing\InvalidUsageDays;
use App\Domain\Licensing\LicensingError;
use App\Domain\Licensing\Package;
use App\Domain\Licensing\PackageKind;
use App\Domain\Licensing\PackageWithoutTools;
use App\Domain\Licensing\UnknownTool;
use App\Infrastructure\Persistence\Eloquent\PackageRecord;
use App\Infrastructure\Persistence\Eloquent\ToolRecord;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Illuminate\Validation\ValidationException;

/**
 * The package form of the create and edit pages: its fields, what they send to the use cases, and a
 * refused value shown on its field.
 *
 * The form only shapes the values; the rules (the name's length, at least one tool, the price, the 7 to
 * 365 days) are the licensing domain's, so the panel cannot write a package the shop could not sell.
 */
final class PackageForm
{
    /**
     * The columns the tool checkboxes are laid out in, so 20-odd tools fit on one screen.
     */
    private const int TOOL_COLUMNS = 2;

    /**
     * The fields: the name, the tools besides the everyday ones, and the price and the days, which the
     * free package has not since it is not sold.
     *
     * @return list<Component>
     */
    public static function components(): array
    {
        return [
            TextInput::make('name')->label(__('admin.packages.fields.name'))->required(),
            CheckboxList::make('tools')
                ->label(__('admin.packages.fields.tools'))
                ->helperText(__('admin.packages.help.essentials'))
                ->options(self::toolOptions())
                ->columns(self::TOOL_COLUMNS),
            TextInput::make('price')
                ->label(__('admin.packages.fields.price'))
                ->prefix('R$')
                ->required()
                ->hidden(self::isFree(...)),
            TextInput::make('days')
                ->label(__('admin.packages.fields.days'))
                ->helperText(__('admin.packages.help.days'))
                ->required()
                ->hidden(self::isFree(...)),
        ];
    }

    /**
     * The values as the use cases take them; a missing or unreadable value becomes one they refuse.
     *
     * @param  array<string, mixed>  $data
     */
    public static function input(array $data): PackageInput
    {
        $tools = is_array($data['tools'] ?? null) ? $data['tools'] : [];
        $days = filter_var($data['days'] ?? null, FILTER_VALIDATE_INT);

        return new PackageInput(
            self::text($data, 'name'),
            array_values(array_filter($tools, is_string(...))),
            self::text($data, 'price'),
            is_int($days) ? $days : 0,
        );
    }

    /**
     * The tools of the package to tick, without the everyday ones every package carries.
     *
     * @return list<string>
     */
    public static function pickedTools(PackageRecord $record): array
    {
        return array_values(array_diff($record->tools->modelKeys(), Package::ESSENTIALS));
    }

    /**
     * The error of the form for a refused value, on the field that holds it.
     */
    public static function refused(LicensingError $error): ValidationException
    {
        $field = match (true) {
            $error instanceof InvalidPrice => 'price',
            $error instanceof InvalidUsageDays => 'days',
            $error instanceof UnknownTool, $error instanceof PackageWithoutTools => 'tools',
            $error instanceof InvalidCatalogueName => 'name',
            default => null,
        };

        return ValidationException::withMessages([
            'data.'.($field ?? 'name') => __('admin.packages.errors.'.($field ?? 'refused')),
        ]);
    }

    /**
     * Whether the form is the free package's.
     */
    private static function isFree(?PackageRecord $record): bool
    {
        return $record?->kind === PackageKind::Free->value;
    }

    /**
     * Every tool but the everyday ones, by name.
     *
     * @return array<string, string>
     */
    private static function toolOptions(): array
    {
        $options = [];

        foreach (ToolRecord::query()->whereNotIn('id', Package::ESSENTIALS)->orderBy('name')->get() as $tool) {
            $options[$tool->id] = $tool->name;
        }

        return $options;
    }

    /**
     * One typed value; a missing one is empty.
     *
     * @param  array<string, mixed>  $data
     */
    private static function text(array $data, string $key): string
    {
        $value = $data[$key] ?? '';

        return is_string($value) || is_int($value) || is_float($value) ? (string) $value : '';
    }
}
