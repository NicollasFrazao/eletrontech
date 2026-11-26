<x-layouts.account :title="__('agenda.title')">
    <form method="GET" action="{{ route('agenda') }}" role="search">
        <label for="search">{{ __('agenda.search') }}</label>
        <input id="search" name="search" type="search" value="{{ $search }}">
        <button type="submit">{{ __('agenda.actions.search') }}</button>
    </form>

    @if (trim($search) !== '')
        <h2>{{ __('agenda.results', ['term' => $search]) }}</h2>
    @else
        <h2>{{ $month->format('F Y') }}</h2>
        <p>
            <a href="{{ route('agenda', ['month' => $previous]) }}">{{ __('agenda.actions.previous') }}</a>
            · <a href="{{ route('agenda', ['month' => $next]) }}">{{ __('agenda.actions.next') }}</a>
        </p>
    @endif

    <ul>
        @forelse ($events as $event)
            <li>
                <strong>{{ $event->details->startsAt->format('Y-m-d H:i') }}</strong> {{ $event->details->title }}
                @if ($event->details->description !== '')
                    <br>{{ $event->details->description }}
                @endif
                · <a href="{{ route('agenda.events.edit', $event->id->value) }}">{{ __('agenda.actions.edit') }}</a>
                <form method="POST" action="{{ route('agenda.events.destroy', $event->id->value) }}" style="display: inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit">{{ __('agenda.actions.delete') }}</button>
                </form>
            </li>
        @empty
            <li>{{ __('agenda.empty') }}</li>
        @endforelse
    </ul>

    <section>
        <form method="POST" action="{{ route('agenda.events.store') }}">
            @csrf
            @include('agenda.form', ['event' => null])
            <button type="submit">{{ __('agenda.actions.add') }}</button>
        </form>
    </section>
    <section>
        @foreach (['pdf' => 'export_pdf', 'txt' => 'export_txt'] as $format => $label)
            <form method="POST" action="{{ route('agenda.export', $format) }}" style="display: inline">
                @csrf
                <button type="submit">{{ __('agenda.actions.'.$label) }}</button>
            </form>
        @endforeach
        @error('export')
            <p class="error" role="alert">{{ $message }}</p>
        @enderror
    </section>
</x-layouts.account>
