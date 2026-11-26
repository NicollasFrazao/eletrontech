<x-layouts.account :title="__('shop.title.licence')">
    @if ($licence === null)
        <p>{{ __('shop.licence.none') }}</p>
    @else
        <p>{{ __('shop.licence.package') }}: <strong>{{ $licence->licence->package->name }}</strong></p>
        <p>{{ __('shop.licence.period', ['start' => $licence->licence->startsAt->format('Y-m-d H:i'), 'end' => $licence->licence->endsAt->format('Y-m-d H:i')]) }}</p>
        @if ($licence->remainingDays > 0)
            <p>{{ __('shop.licence.remaining', ['days' => $licence->remainingDays]) }}</p>
        @else
            <p class="error">{{ __('shop.licence.ended') }}</p>
        @endif
        <h2>{{ __('shop.tools') }}</h2>
        <ul>
            @foreach ($licence->tools as $tool)
                <li>{{ $tool->name }}</li>
            @endforeach
        </ul>
        <p>{{ __('shop.licence.renewal') }}</p>
    @endif
    <p><a href="{{ route('shop') }}">{{ __('shop.actions.shop') }}</a></p>
</x-layouts.account>
