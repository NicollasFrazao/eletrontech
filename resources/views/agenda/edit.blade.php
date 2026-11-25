<x-layouts.account :title="$event->details->title">
    <form method="POST" action="{{ route('agenda.events.update', $event->id->value) }}">
        @csrf
        @method('PUT')
        @include('agenda.form', ['event' => $event])
        <button type="submit">{{ __('agenda.actions.save') }}</button>
    </form>
    <p><a href="{{ route('agenda') }}">{{ __('agenda.title') }}</a></p>
</x-layouts.account>
