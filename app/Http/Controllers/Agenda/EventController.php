<?php

declare(strict_types=1);

namespace App\Http\Controllers\Agenda;

use App\Application\Agenda\ExportEvents;
use App\Application\Agenda\RecordEvent;
use App\Application\Agenda\ShowAgenda;
use App\Domain\Agenda\EventNotFound;
use App\Domain\Agenda\ExportFormat;
use App\Domain\Agenda\InvalidEvent;
use App\Domain\Files\FileTooLarge;
use App\Domain\Files\NameTaken;
use App\Domain\Files\QuotaExceeded;
use App\Http\Controllers\Account\SignedInAccount;
use App\Http\Requests\Agenda\EventRequest;
use DateTimeImmutable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The signed-in person's events (the 2015 `Eventos.php`): a month, a search, adding, editing and deleting
 * one, and exporting them all to the file manager. Another person's event is not found.
 */
final readonly class EventController
{
    public function __construct(
        private ShowAgenda $agenda,
        private RecordEvent $record,
        private ExportEvents $export,
    ) {}

    /**
     * The events of a month (`?month=YYYY-MM`, this month by default), or those a search finds.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws NotFoundHttpException When the month is not "YYYY-MM".
     */
    public function index(Request $request): View
    {
        $owner = SignedInAccount::id($request);
        $month = $request->string('month', Date::now()->format('Y-m'))->toString();
        $search = $request->string('search')->toString();

        try {
            $events = trim($search) === '' ? $this->agenda->month($owner, $month) : $this->agenda->search($owner, $search);
        } catch (InvalidArgumentException $error) {
            throw new NotFoundHttpException($error->getMessage(), $error);
        }

        $first = new DateTimeImmutable($month.'-01');

        return view('agenda.index', [
            'events' => $events,
            'month' => $first,
            'previous' => $first->modify('-1 month')->format('Y-m'),
            'next' => $first->modify('+1 month')->format('Y-m'),
            'search' => $search,
        ]);
    }

    /**
     * Adds an event.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws ValidationException When a value is refused.
     */
    public function store(EventRequest $request): RedirectResponse
    {
        try {
            $this->record->add(SignedInAccount::id($request), $request->toInput());
        } catch (InvalidEvent $error) {
            throw ValidationException::withMessages([$error->field->value => __("agenda.errors.{$error->field->value}")]);
        }

        return back()->with('status', __('agenda.status.added'));
    }

    /**
     * The form to edit one event.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws NotFoundHttpException When the event is not the person's.
     */
    public function edit(Request $request, int $event): View
    {
        try {
            return view('agenda.edit', ['event' => $this->agenda->event(SignedInAccount::id($request), $event)]);
        } catch (EventNotFound $error) {
            throw new NotFoundHttpException($error->getMessage(), $error);
        }
    }

    /**
     * Saves new details for one event.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws ValidationException When a value is refused.
     * @throws NotFoundHttpException When the event is not the person's.
     */
    public function update(EventRequest $request, int $event): RedirectResponse
    {
        try {
            $this->record->update(SignedInAccount::id($request), $event, $request->toInput());
        } catch (InvalidEvent $error) {
            throw ValidationException::withMessages([$error->field->value => __("agenda.errors.{$error->field->value}")]);
        } catch (EventNotFound $error) {
            throw new NotFoundHttpException($error->getMessage(), $error);
        }

        return redirect()->route('agenda')->with('status', __('agenda.status.updated'));
    }

    /**
     * Deletes one event.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws NotFoundHttpException When the event is not the person's.
     */
    public function destroy(Request $request, int $event): RedirectResponse
    {
        try {
            $this->record->delete(SignedInAccount::id($request), $event);
        } catch (EventNotFound $error) {
            throw new NotFoundHttpException($error->getMessage(), $error);
        }

        return redirect()->route('agenda')->with('status', __('agenda.status.deleted'));
    }

    /**
     * Exports every event to a PDF or text file in the folder "Events" and opens the file manager there.
     *
     * @throws AuthenticationException When nobody is signed in.
     * @throws ValidationException When the file does not fit or an export of the same second exists.
     */
    public function export(Request $request, string $format): RedirectResponse
    {
        $owner = SignedInAccount::id($request);

        try {
            $this->export->handle($owner, ExportFormat::from($format));
        } catch (FileTooLarge|QuotaExceeded) {
            throw ValidationException::withMessages(['export' => __('files.errors.quota')]);
        } catch (NameTaken) {
            throw ValidationException::withMessages(['export' => __('agenda.errors.export_again')]);
        }

        return redirect()->route('files.root')->with('status', __('agenda.status.exported'));
    }
}
