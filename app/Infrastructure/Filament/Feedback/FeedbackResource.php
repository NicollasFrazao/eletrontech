<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Feedback;

use App\Domain\Feedback\FeedbackSubject;
use App\Infrastructure\Persistence\Eloquent\FeedbackRecord;
use BackedEnum;
use Filament\Resources\Resource as FilamentResource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Override;

/**
 * The feedback messages in the admin panel (E9, #63), newest first: when, who, the subject and the text.
 *
 * The panel only reads them: a message is written by its author through the feedback form, and nothing
 * here changes or deletes it.
 *
 * @extends FilamentResource<FeedbackRecord>
 */
final class FeedbackResource extends FilamentResource
{
    protected static ?string $model = FeedbackRecord::class;

    protected static ?string $slug = 'feedback';

    protected static bool $isGloballySearchable = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftEllipsis;

    /**
     * "Feedback message", as the panel names one.
     */
    #[Override]
    public static function getModelLabel(): string
    {
        return __('admin.feedback.label');
    }

    /**
     * "Feedback", as the navigation and the list name them.
     */
    #[Override]
    public static function getPluralModelLabel(): string
    {
        return __('admin.feedback.plural_label');
    }

    /**
     * No message is written here: a person sends it from the feedback form.
     */
    #[Override]
    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * The list: the date, the author, the subject and the whole message, searched by author or text.
     */
    #[Override]
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sent_at')->label(__('admin.feedback.columns.sent_at'))->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('author.name')->label(__('admin.feedback.columns.author'))->searchable(),
                TextColumn::make('author.email')->label(__('accounts.fields.email'))->searchable(),
                TextColumn::make('subject')
                    ->label(__('feedback.fields.subject'))
                    ->formatStateUsing(static fn (string $state): string => __("feedback.subjects.{$state}")),
                TextColumn::make('message')->label(__('feedback.fields.message'))->wrap()->searchable(),
            ])
            ->filters([
                SelectFilter::make('subject')->label(__('feedback.fields.subject'))->options(self::subjects()),
            ])
            ->defaultSort('sent_at', 'desc');
    }

    /**
     * The list only.
     */
    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListFeedback::route('/'),
        ];
    }

    /**
     * The subjects, each with its label.
     *
     * @return array<string, string>
     */
    private static function subjects(): array
    {
        $subjects = [];

        foreach (FeedbackSubject::cases() as $subject) {
            $subjects[$subject->value] = __("feedback.subjects.{$subject->value}");
        }

        return $subjects;
    }
}
