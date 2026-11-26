<?php

declare(strict_types=1);

namespace App\Infrastructure\Filament\Tools;

use App\Application\Administration\RenameTool;
use App\Domain\Licensing\InvalidCatalogueName;
use App\Domain\Licensing\ToolNameTaken;
use App\Domain\Licensing\UnknownTool;
use App\Infrastructure\Persistence\Eloquent\ToolRecord;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use LogicException;
use Override;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The edit page of a tool: its name, saved by the RenameTool use case.
 */
final class EditTool extends EditRecord
{
    protected static string $resource = ToolResource::class;

    /**
     * Saves the name through the use case; a refused name is shown on its field.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException When the name is refused.
     * @throws NotFoundHttpException When the tool is gone.
     * @throws LogicException When the record is not a tool's row.
     */
    #[Override]
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof ToolRecord) {
            throw new LogicException('The tool pages edit the rows of tools only.');
        }

        $name = $data['name'] ?? '';

        try {
            App::make(RenameTool::class)->handle($record->id, is_string($name) ? $name : '');
        } catch (UnknownTool $unknown) {
            throw new NotFoundHttpException(message: $unknown->getMessage(), previous: $unknown);
        } catch (ToolNameTaken) {
            throw ValidationException::withMessages(['data.name' => __('admin.tools.errors.taken')]);
        } catch (InvalidCatalogueName) {
            throw ValidationException::withMessages(['data.name' => __('admin.tools.errors.name')]);
        }

        return $record->refresh();
    }
}
